<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\MemberProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class HimsiRegistrationController extends Controller
{
    public function create(): View
    {
        return view('portal.register');
    }

    public function success(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('himsi_registration_id')) {
            return redirect()->route('registration.create');
        }

        return view('portal.success');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $validator = Validator::make($request->all(), array_merge(MemberProfile::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9.-]+$/', 'unique:himsi_registrations,nim'],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'active_letter' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'student_card' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]), [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar.',
            'email' => 'Masukkan alamat email yang valid.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'phone.regex' => 'Nomor HP harus berisi 9–15 angka, boleh diawali +.',
            'mimes' => ':attribute harus berupa JPG, PNG, atau PDF.',
            'certificate.max' => 'Sertifikat maksimal 5 MB.',
            'active_letter.max' => 'Surat aktif kuliah maksimal 5 MB.',
            'student_card.max' => 'KTM maksimal 5 MB.',
        ], array_merge(MemberProfile::labels(), [
            'name' => 'Nama lengkap', 'nim' => 'NIM', 'angkatan' => 'Angkatan',
            'phone' => 'Nomor HP', 'certificate' => 'Sertifikat CB',
            'active_letter' => 'Surat aktif kuliah', 'student_card' => 'KTM',
        ]));
        // Jangan pernah menyimpan password atau berkas ke old input/session.
        $safeInput = $request->only(['name', 'nim', 'angkatan', 'email', 'phone', ...array_keys(MemberProfile::labels())]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($safeInput);
        }
        $data = $validator->validated();
        $disk = Storage::build([
            'driver' => 'local', 'root' => storage_path('app/private/himsi-registration'), 'throw' => true,
        ]);
        $paths = [];
        try {
            foreach (['certificate', 'active_letter', 'student_card'] as $field) {
                $paths[$field] = $disk->putFile('documents', $request->file($field));
                if (! $paths[$field]) {
                    throw new \RuntimeException('Upload gagal.');
                }
            }
            $id = DB::transaction(function () use ($data, $paths) {
                $user = new User;
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);
                $user->role = 'pending';
                $user->is_active = false;
                $user->save();

                return DB::table('himsi_registrations')->insertGetId(array_merge(Arr::only($data, array_keys(MemberProfile::labels())), [
                    'user_id' => $user->id, 'nim' => $data['nim'], 'angkatan' => $data['angkatan'],
                    'program_studi' => 'Sistem Informasi', 'phone' => $data['phone'], 'status' => 'pending',
                    'certificate_path' => $paths['certificate'], 'active_letter_path' => $paths['active_letter'],
                    'student_card_path' => $paths['student_card'], 'created_at' => now(), 'updated_at' => now(),
                ]));
            });
        } catch (Throwable $exception) {
            foreach ($paths as $path) {
                if ($path) {
                    $disk->delete($path);
                }
            }
            if ($exception instanceof QueryException && in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return back()->withErrors(['registration' => 'Email atau NIM sudah terdaftar.'])->withInput($safeInput);
            }
            report($exception);

            return back()->withErrors(['registration' => 'Pendaftaran belum tersimpan. Silakan coba lagi.'])->withInput($safeInput);
        }
        $request->session()->put('himsi_registration_id', $id);

        return redirect()->route('registration.success');
    }
}
