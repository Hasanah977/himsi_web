<?php

namespace App\Http\Controllers;

use App\Models\PublicContent;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Appearance;
use App\Support\Governance;
use App\Support\PublicImage;
use App\Support\SecurityAudit;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    private function schema(string $section): array
    {
        $schema = config('superadmin.sections.'.$section);

        abort_unless(is_array($schema), 404);

        return $schema;
    }

    private function checkContent(
        string $section,
        PublicContent $content
    ): void {
        abort_unless($content->section === $section, 404);
    }

    public function dashboard(Request $request): View
    {
        $days = $request->integer('days') === 30 ? 30 : 7;
        $start = now()->startOfDay()->subDays($days - 1);
        $activity = SecurityEvent::where('created_at', '>=', $start)->get(['created_at'])
            ->countBy(fn (SecurityEvent $event) => $event->created_at->format('Y-m-d'));
        $activitySeries = collect(range(0, $days - 1))->map(fn (int $offset) => [
            'label' => $start->copy()->addDays($offset)->format('d/m'),
            'count' => $activity->get($start->copy()->addDays($offset)->format('Y-m-d'), 0),
        ]);
        $roles = User::query()->select('role')->get()->countBy('role');
        $accountGroups = [
            'KBH' => $roles->get('kbh', 0),
            'Pengurus' => collect([...User::OFFICER_ROLES, 'koordinator_pengawas'])->sum(fn (string $role) => $roles->get($role, 0)),
            'Panitia' => $roles->get('panitia_cb', 0) + $roles->get('panitia_kpum', 0),
            'Super Admin' => $roles->get('super_admin', 0),
        ];
        $accountGroups['Lainnya / belum ditentukan'] = $roles->sum() - array_sum($accountGroups);
        $contentCounts = [];
        foreach (['gallery' => 'Galeri', 'announcements' => 'Pengumuman', 'programs' => 'Program Kerja', 'achievements' => 'Prestasi'] as $section => $label) {
            $contentCounts[$label] = PublicContent::where('section', $section)->where('is_published', true)->whereNull('department')->count();
        }
        $programStatuses = collect(PublicContent::items('programs'))->countBy('status');

        return view('admin.dashboard', [
            'days' => $days, 'activitySeries' => $activitySeries, 'accountGroups' => $accountGroups,
            'contentCounts' => $contentCounts, 'programStatuses' => $programStatuses,
            'usersCount' => User::count(),
            'publishedCount' => PublicContent::where(
                'section',
                '!=',
                'settings'
            )->where('is_published', true)->count(),
            'draftCount' => PublicContent::where(
                'section',
                '!=',
                'settings'
            )->where('is_published', false)->count(),
            'pendingCount' => User::where('role', 'pending')->count(),
        ]);
    }

    public function index(string $section)
    {
        $schema = $this->schema($section);

        if ($section === 'settings') {
            $content = PublicContent::where('section', 'settings')
                ->where('slug', 'site')
                ->firstOrFail();

            return redirect()->route('admin.content.edit', [
                'section' => $section,
                'content' => $content,
            ]);
        }

        $contents = PublicContent::where('section', $section)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view(
            'admin.content-index',
            compact('section', 'schema', 'contents')
        );
    }

    public function create(string $section)
    {
        $schema = $this->schema($section);

        abort_if($section === 'settings', 404);

        return view('admin.content-form', [
            'section' => $section,
            'schema' => $schema,
            'content' => new PublicContent([
                'payload' => [],
                'is_published' => true,
                'position' => 0,
            ]),
        ]);
    }

    public function store(Request $request, string $section)
    {
        $this->schema($section);

        abort_if($section === 'settings', 404);

        $content = new PublicContent;
        $content->section = $section;

        $this->saveContent($request, $section, $content);

        return redirect()
            ->route('admin.content.index', $section)
            ->with('success', 'Konten berhasil ditambahkan.');
    }

    public function edit(
        string $section,
        PublicContent $content
    ) {
        $schema = $this->schema($section);
        $this->checkContent($section, $content);

        return view(
            'admin.content-form',
            compact('section', 'schema', 'content')
        );
    }

    public function update(
        Request $request,
        string $section,
        PublicContent $content
    ) {
        $this->schema($section);
        $this->checkContent($section, $content);

        $this->saveContent($request, $section, $content);

        return redirect()
            ->route('admin.content.index', $section)
            ->with('success', 'Perubahan berhasil disimpan.');
    }

    private function saveContent(
        Request $request,
        string $section,
        PublicContent $content
    ): void {
        $schema = $this->schema($section);

        $rules = [
            'position' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_published' => ['sometimes', 'boolean'],
        ];

        $attributes = [];

        if ($section === 'announcements') {
            $rules['department'] = ['nullable', Rule::in(array_keys(config('superadmin.departments')))];
        }

        foreach ($schema['fields'] as $key => $field) {
            [$label, $type] = $field;

            if ($type === 'image') {
                $rules['uploads.'.$key] = [
                    $section === 'hero_slides' && empty($content->payload[$key]) ? 'required' : 'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:3072',
                ];

                $attributes['uploads.'.$key] = $label;

                continue;
            }

            $rules['data.'.$key] = match ($type) {
                'number' => [
                    'required',
                    'integer',
                    'min:0',
                    'max:1000000',
                ],
                'select' => [
                    'required',
                    'string',
                    Rule::in($field[2]),
                ],
                'date' => [
                    'required',
                    'string',
                    'date_format:Y-m-d',
                    'after_or_equal:0001-01-01',
                    'before_or_equal:9999-12-31',
                ],
                'textarea', 'lines' => [
                    'required',
                    'string',
                    'max:20000',
                ],
                default => [
                    'required',
                    'string',
                    'max:255',
                ],
            };

            $attributes['data.'.$key] = $label;
            if ($section === 'departments' && $key === 'members') {
                $rules['data.'.$key][0] = 'nullable';
            }
        }

        $hasSlug = in_array(
            $section,
            ['programs', 'announcements', 'achievements'],
            true
        );

        if ($hasSlug) {
            $rules['slug'] = [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('public_contents', 'slug')
                    ->where('section', $section)
                    ->ignore($content->id),
            ];
        }

        $validated = $request->validate(
            $rules,
            [
                'required' => ':attribute wajib diisi.',
                'in' => 'Pilihan :attribute tidak tersedia.',
                'date_format' => ':attribute harus dipilih melalui kalender.',
                'after_or_equal' => ':attribute berada di luar rentang yang diperbolehkan.',
                'before_or_equal' => ':attribute berada di luar rentang yang diperbolehkan.',
                'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
                'slug.unique' => 'Slug sudah digunakan. Silakan gunakan slug lain.',
            ],
            $attributes
        );

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $payload = [];

        foreach ($schema['fields'] as $key => $field) {
            $type = $field[1];

            if ($type === 'image') {
                $payload[$key] = $content->payload[$key]
                    ?? 'placeholder.svg';

                if ($request->hasFile('uploads.'.$key)) {
                    $path = PublicImage::store($request->file('uploads.'.$key), 'uploads.'.$key);

                    abort_unless($path, 500, 'Gagal menyimpan gambar.');

                    $payload[$key] = 'storage/'.$path;
                }

                continue;
            }

            $value = $validated['data'][$key] ?? '';

            if ($type === 'date') {
                // Form mengirim YYYY-MM-DD.
                // Simpan sebagai tanggal Indonesia agar sesuai tampilan publik.
                [$year, $month, $day] = explode('-', $value);

                $payload[$key] = (int) $day
                    .' '.$monthNames[(int) $month]
                    .' '.$year;

                continue;
            }

            $payload[$key] = match ($type) {
                'number' => (int) $value,
                'lines' => array_values(array_filter(
                    array_map(
                        'trim',
                        preg_split('/\R/', trim($value))
                    ),
                    fn ($line) => $line !== ''
                )),
                default => trim($value),
            };
        }

        $content->payload = $payload;
        if ($section === 'announcements') {
            $content->department = $validated['department'] ?? null;
        }
        $content->position = $validated['position'];

        $content->is_published = $section === 'settings'
            ? true
            : $request->boolean('is_published');

        if ($hasSlug) {
            $content->slug = $validated['slug'];
        }

        $content->save();
        if ($section === 'settings') {
            Appearance::remember($payload);
        }
    }

    public function destroy(
        string $section,
        PublicContent $content
    ) {
        $this->schema($section);
        $this->checkContent($section, $content);

        abort_if($section === 'settings', 403);

        $content->delete();

        return back()->with('success', 'Konten berhasil dihapus.');
    }

    public function users(Request $request): View
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim($validated['search'] ?? '');
        $users = User::query();

        if ($search !== '') {
            $users->where(function (Builder $query) use ($search): void {
                $query->whereLike('name', '%'.$search.'%')
                    ->orWhereLike('email', '%'.$search.'%');
            });
        }

        return view('admin.users', [
            'users' => $users->orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    public function createUser()
    {
        return view('admin.user-form', [
            'account' => new User([
                'role' => 'pending',
                'is_active' => true,
            ]),
        ]);
    }

    private function decryptAccount(string $encryptedAccount): User
    {
        try {
            $id = Crypt::decryptString($encryptedAccount);
        } catch (DecryptException $exception) {
            abort(404);
        }
        abort_unless(ctype_digit($id), 404);

        return User::findOrFail($id);
    }

    public function editUser(string $encryptedAccount): View
    {
        $account = $this->decryptAccount($encryptedAccount);

        return view('admin.user-form', [
            'account' => $account,
            'accountHistory' => SecurityEvent::whereIn('event', ['account_appointment', 'account_status'])->where('context->account_id', $account->id)->latest('id')->limit(30)->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $this->saveUser($request, new User);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Akun berhasil dibuat.');
    }

    public function updateUser(Request $request, string $encryptedAccount): RedirectResponse
    {
        $account = $this->decryptAccount($encryptedAccount);
        DB::transaction(function () use ($request, $account): void {
            $this->saveUser($request, User::whereKey($account->id)->lockForUpdate()->firstOrFail());
        });

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    private function saveUser(Request $request, User $account): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($account->id),
            ],
            'password' => [
                $account->exists ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'role' => [
                'required',
                Rule::in(array_keys(config('superadmin.roles'))),
            ],
            'department' => [
                Rule::requiredIf(fn () => in_array(
                    $request->input('role'),
                    ['koordinator_departemen', 'anggota_departemen'],
                    true
                )),
                'nullable',
                Rule::in(array_keys(config('superadmin.departments'))),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($account->exists && $account->isStudentAccount()) {
            abort_unless($data['role'] === $account->assignedRole() && ($data['department'] ?? null) === $account->department, 422, 'Gunakan Kelola Jabatan untuk mengubah jabatan mahasiswa.');
        } else {
            abort_if(in_array($data['role'], User::OFFICER_ROLES, true), 422, 'Buat akun KBH terlebih dahulu, lalu tambahkan jabatan melalui Kelola Jabatan.');
        }
        if ($account->exists) {
            abort_unless($request->boolean('is_active') === $account->is_active, 422, 'Gunakan pengaturan status akun untuk mengubah akses login.');
        }

        // Akun yang sedang dipakai tidak boleh kehilangan akses sendiri.
        if ($account->id === $request->user()->id) {
            abort_unless(
                $data['role'] === 'super_admin'
                && $request->boolean('is_active'),
                422,
                'Anda tidak boleh menonaktifkan atau menurunkan role akun sendiri.'
            );
        }

        $account->name = $data['name'];
        $account->email = $data['email'];
        $account->role = $data['role'];
        $account->is_active = $request->boolean('is_active');

        $account->department = in_array(
            $data['role'],
            ['koordinator_departemen', 'anggota_departemen'],
            true
        ) ? $data['department'] : null;

        if (! empty($data['password'])) {
            $account->password = Hash::make($data['password']);
        }

        $account->save();
    }

    public function appointment(Request $request, string $encryptedAccount): RedirectResponse
    {
        $account = $this->decryptAccount($encryptedAccount);
        $data = $request->validate([
            'appointment_role' => ['required', Rule::in(['kbh', ...User::OFFICER_ROLES])],
            'appointment_department' => [Rule::requiredIf(in_array($request->input('appointment_role'), ['koordinator_departemen', 'anggota_departemen'], true)), 'nullable', Rule::in(array_keys(config('superadmin.departments')))],
            'period' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'max:1000'],
            'expected_role' => ['required', 'string'],
            'expected_department' => ['nullable', 'string'],
        ]);
        DB::transaction(function () use ($account, $data): void {
            $account = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            abort_unless($account->isStudentAccount(), 422, 'Jabatan hanya dapat diberikan kepada akun KBH.');
            abort_unless($account->assignedRole() === $data['expected_role'] && $account->department === ($data['expected_department'] ?? null), 409, 'Jabatan telah berubah. Muat ulang halaman sebelum melanjutkan.');
            if ($data['appointment_role'] !== 'kbh') {
                Governance::assertWritable();
            }
            $before = ['role' => $account->assignedRole(), 'department' => $account->department];
            $account->role = $data['appointment_role'];
            $account->department = in_array($data['appointment_role'], ['koordinator_departemen', 'anggota_departemen'], true) ? $data['appointment_department'] : null;
            $account->save();
            $data['period'] = Governance::active()?->name ?? $data['period'];
            SecurityAudit::record('account_appointment', 'Jabatan '.$account->name.' diperbarui.', context: [
                'account_id' => $account->id, 'before' => $before,
                'after' => ['role' => $account->assignedRole(), 'department' => $account->department],
                'period' => $data['period'], 'reason' => $data['reason'],
            ], affected: 1);
        });

        return back()->with('success', $data['appointment_role'] === 'kbh' ? 'Jabatan dicabut. Akun dan riwayat KBH tetap tersimpan.' : 'Jabatan berhasil disimpan. Toggle mengikuti jabatan terbaru.');
    }

    public function accountStatus(Request $request, string $encryptedAccount): RedirectResponse
    {
        $account = $this->decryptAccount($encryptedAccount);
        $data = $request->validate(['active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $account, $data): void {
            $account = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            abort_if($account->id === $request->user()->id || $account->assignedRole() === 'super_admin', 403, 'Akun Super Admin tidak dapat dinonaktifkan dari menu ini.');
            $before = $account->is_active;
            $account->is_active = (bool) $data['active'];
            $account->remember_token = null;
            $account->save();
            if (! $account->is_active && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $account->id)->delete();
            }
            SecurityAudit::record('account_status', 'Status akun '.$account->name.' diperbarui.', context: ['account_id' => $account->id, 'before' => $before, 'after' => $account->is_active, 'reason' => $data['reason']], affected: 1);
        });

        return back()->with('success', $data['active'] ? 'Akun diaktifkan kembali.' : 'Akun dinonaktifkan. Data dan riwayat tetap tersimpan.');
    }

    public function destroyUser(Request $request, string $encryptedAccount): RedirectResponse
    {
        abort(403, 'Penghapusan permanen dinonaktifkan. Gunakan pengaturan status akun.');
    }

    public function module(
        string $role,
        ?string $department = null
    ) {
        $roles = config('superadmin.roles');

        abort_unless(
            isset($roles[$role])
            && ! in_array($role, ['pending', 'super_admin'], true),
            404
        );

        $isDepartment = in_array(
            $role,
            ['koordinator_departemen', 'anggota_departemen'],
            true
        );

        if ($isDepartment) {
            abort_unless(
                $department !== null
                && array_key_exists(
                    $department,
                    config('superadmin.departments')
                ),
                404
            );
        } else {
            abort_if($department !== null, 404);
        }

        $key = $role.($department ? '.'.$department : '');

        $modules = config('superadmin.modules', []);
        $links = $modules[$key] ?? [];

        $moduleTitle = $roles[$role];

        if ($department) {
            $moduleTitle .= ' — '
                .config('superadmin.departments')[$department];
        }

        return view(
            'admin.module',
            compact('moduleTitle', 'links')
        );
    }
}
