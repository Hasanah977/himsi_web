<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\OpsGroup;
use App\Models\OpsMessage;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ForumController extends Controller
{
    private function groups(Request $request, string $department): Builder
    {
        return OpsGroup::where(function ($q) use ($request, $department) {
            $q->where('system_key', 'umum')->orWhere(function ($q) use ($request, $department) {
                $q->where('department', $department);
                if (! $request->user()->isSuperAdmin()) {
                    $q->where(fn ($q) => $q->whereNotNull('system_key')->orWhereHas('members', fn ($m) => $m->where('users.id', $request->user()->id)));
                }
            });
        });
    }

    public function index(Request $request, string $department): View
    {
        $request->validate(['group' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:120'], 'search' => ['nullable', 'string', 'max:120']]);
        $base = $this->groups($request, $department);
        $selected = $request->filled('group') ? (clone $base)->findOrFail($request->integer('group')) : (clone $base)->orderBy('id')->first();
        $query = clone $base;
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->input('q').'%');
        }
        $groups = $query->with('latestMessage.sender')->withCount(['messages as unread_count' => function ($q) use ($request) {
            $q->where('user_id', '!=', $request->user()->id)->whereRaw('ops_messages.id > COALESCE((SELECT last_message_id FROM ops_reads WHERE ops_reads.group_id = ops_messages.group_id AND ops_reads.user_id = ?), 0)', [$request->user()->id]);
        }])->orderBy('id')->get();
        $messages = null;
        $members = collect();
        $latestId = 0;
        if ($selected) {
            $query = $selected->messages()->with('sender');
            if ($request->filled('search')) {
                $query->where('body', 'like', '%'.$request->input('search').'%');
            }
            $messages = $query->orderByDesc('id')->paginate(25)->withQueryString();
            $latestId = $selected->messages()->max('id') ?? 0;
            if (! $request->filled('search')) {
                DB::transaction(function () use ($selected, $request, $messages) {
                    DB::table('ops_reads')->insertOrIgnore(['group_id' => $selected->id, 'user_id' => $request->user()->id, 'last_message_id' => 0]);
                    $read = DB::table('ops_reads')->where('group_id', $selected->id)->where('user_id', $request->user()->id)->lockForUpdate()->first();
                    DB::table('ops_reads')->where('id', $read->id)->update(['last_message_id' => max($read->last_message_id, $messages->max('id') ?? 0)]);
                });
            }
            $members = $selected->system_key ? ($selected->department ? Flow::people($selected->department)->orderBy('name')->get(['id', 'name', 'role']) : User::where('is_active', true)->whereIn('role', array_diff(array_keys(config('superadmin.roles')), ['pending']))->orderBy('name')->get(['id', 'name', 'role'])) : $selected->members()->orderBy('name')->get(['users.id', 'name', 'role']);
        }

        $attachments = $selected?->messages()->whereNotNull('file_path')->latest('id')->limit(20)->get() ?? collect();

        return view('department.forum', ['attachments' => $attachments, 'groups' => $groups, 'selected' => $selected, 'messages' => $messages, 'members' => $members, 'latestId' => $latestId, 'people' => Flow::people($department)->orderBy('name')->get(['id', 'name', 'role'])]);
    }

    public function create(Request $request, string $department): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'members' => ['required', 'array', 'min:1', 'max:200'], 'members.*' => ['integer', 'distinct']]);
        $people = Flow::people($department)->whereIn('id', $data['members'])->pluck('id');
        Flow::require($people->count() === count($data['members']), 'Anggota grup harus akun aktif di departemen yang sama.');
        $group = DB::transaction(function () use ($data, $department, $people, $request) {
            $g = OpsGroup::create(['department' => $department, 'name' => $data['name'], 'description' => $data['description'] ?? null, 'created_by' => $request->user()->id]);
            $g->members()->sync($people->push($request->user()->id)->unique()->all());

            return $g;
        });

        return redirect()->route('department.forum', [$department, 'group' => $group->id])->with('success', 'Grup diskusi dibuat.');
    }

    public function send(Request $request, string $department, int $group): RedirectResponse
    {
        $group = $this->groups($request, $department)->findOrFail($group);
        $data = $request->validate(['body' => ['nullable', 'required_without:file', 'string', 'max:10000'], 'file' => ['nullable', 'required_without:body', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240']]);
        $path = null;
        try {
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('department/forum', 'local');
                Flow::require((bool) $path, 'Lampiran gagal disimpan.');
            }
            OpsMessage::create(['group_id' => $group->id, 'user_id' => $request->user()->id, 'body' => $data['body'] ?? null, 'file_path' => $path, 'file_name' => $path ? $request->file('file')->getClientOriginalName() : null]);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect()->route('department.forum', [$department, 'group' => $group->id]);
    }

    public function updates(Request $request, string $department, int $group): JsonResponse
    {
        $group = $this->groups($request, $department)->findOrFail($group);

        return response()->json(['latest_id' => $group->messages()->max('id') ?? 0]);
    }

    public function file(Request $request, string $department, int $message): StreamedResponse
    {
        $message = OpsMessage::whereIn('group_id', $this->groups($request, $department)->select('id'))->findOrFail($message);

        return Flow::file($message->file_path, $message->file_name);
    }

    public function update(Request $request, string $department, int $message): RedirectResponse
    {
        $message = OpsMessage::whereIn('group_id', $this->groups($request, $department)->select('id'))->findOrFail($message);
        abort_unless($message->user_id === $request->user()->id, 403);
        $data = $request->validate(['body' => [$message->file_path ? 'nullable' : 'required', 'string', 'max:10000']]);
        $message->update(['body' => $data['body'] ?? null]);

        return redirect()->route('department.forum', [$department, 'group' => $message->group_id])->with('success', 'Pesan diperbarui.');
    }

    public function destroy(Request $request, string $department, int $message): RedirectResponse
    {
        $message = OpsMessage::whereIn('group_id', $this->groups($request, $department)->select('id'))->findOrFail($message);
        abort_unless($message->user_id === $request->user()->id, 403);
        $path = $message->file_path;
        $group = $message->group_id;
        $message->delete();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('department.forum', [$department, 'group' => $group])->with('success', 'Pesan dihapus.');
    }
}
