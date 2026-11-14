<?php

namespace App\Http\Controllers\kbh;

use App\Http\Controllers\Controller;
use App\Models\OpsGroup;
use App\Models\OpsMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ForumController extends Controller
{
    private function authorizeMember(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->role === 'kbh', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeMember($request);
        $request->validate(['group' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:120'], 'search' => ['nullable', 'string', 'max:120']]);
        $groups = OpsGroup::where('department', 'kbh')->with('latestMessage.sender')->withCount(['messages as unread_count' => function ($query) use ($request): void {
            $query->where('user_id', '!=', $request->user()->id)->whereRaw('ops_messages.id > COALESCE((SELECT last_message_id FROM ops_reads WHERE ops_reads.group_id = ops_messages.group_id AND ops_reads.user_id = ?), 0)', [$request->user()->id]);
        }])->orderBy('id')->get();
        $selected = $request->filled('group') ? $groups->firstWhere('id', $request->integer('group')) : $groups->first();
        abort_if($request->filled('group') && ! $selected, 404);
        $messages = $selected?->messages()->with('sender')->when($request->filled('search'), fn ($query) => $query->where('body', 'like', '%'.$request->input('search').'%'))->latest('id')->paginate(30)->withQueryString();
        if ($request->filled('q')) {
            $groups = $groups->filter(fn (OpsGroup $group): bool => str_contains(mb_strtolower($group->name), mb_strtolower($request->input('q'))));
        }
        $attachments = $selected?->messages()->whereNotNull('file_path')->latest('id')->limit(20)->get() ?? collect();
        if ($selected && ! $request->filled('search') && $messages->isNotEmpty()) {
            DB::transaction(function () use ($request, $selected, $messages): void {
                DB::table('ops_reads')->insertOrIgnore(['group_id' => $selected->id, 'user_id' => $request->user()->id, 'last_message_id' => 0]);
                $read = DB::table('ops_reads')->where('group_id', $selected->id)->where('user_id', $request->user()->id)->lockForUpdate()->first();
                DB::table('ops_reads')->where('id', $read->id)->update(['last_message_id' => max($read->last_message_id, $messages->max('id'))]);
            });
        }
        $members = User::whereIn('role', ['kbh', ...User::OFFICER_ROLES])->where('is_active', true)->whereDoesntHave('registration', fn ($query) => $query->where('status', '!=', 'approved'))->orderBy('name')->get(['id', 'name', 'role']);
        $memberCount = $members->count();

        return view('kbh.forum', compact('groups', 'selected', 'messages', 'memberCount', 'members', 'attachments'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000']]);
        $group = OpsGroup::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'department' => 'kbh', 'created_by' => $request->user()->id]);

        return redirect()->route('kbh.forum', ['group' => $group->id])->with('success', 'Grup dibuat untuk seluruh anggota KBH.');
    }

    public function send(Request $request, int $group): RedirectResponse
    {
        $this->authorizeMember($request);
        $group = OpsGroup::where('department', 'kbh')->findOrFail($group);
        $data = $request->validate(['body' => ['nullable', 'required_without:file', 'string', 'max:10000'], 'file' => ['nullable', 'required_without:body', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240']]);
        $path = $request->file('file')?->store('kbh/forum', 'local');
        try {
            OpsMessage::create(['group_id' => $group->id, 'user_id' => $request->user()->id, 'body' => $data['body'] ?? null, 'file_path' => $path, 'file_name' => $request->file('file')?->getClientOriginalName()]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('kbh.forum', ['group' => $group->id]);
    }

    public function file(Request $request, int $message): StreamedResponse
    {
        $this->authorizeMember($request);
        $message = OpsMessage::whereHas('group', fn ($query) => $query->where('department', 'kbh'))->findOrFail($message);
        abort_unless($message->file_path && Storage::disk('local')->exists($message->file_path), 404);

        return Storage::disk('local')->download($message->file_path, $message->file_name);
    }

    public function update(Request $request, int $message): RedirectResponse
    {
        $this->authorizeMember($request);
        $message = OpsMessage::whereHas('group', fn ($query) => $query->where('department', 'kbh'))->findOrFail($message);
        abort_unless($message->user_id === $request->user()->id, 403);
        $data = $request->validate(['body' => [$message->file_path ? 'nullable' : 'required', 'string', 'max:10000']]);
        $message->update(['body' => $data['body'] ?? null]);

        return redirect()->route('kbh.forum', ['group' => $message->group_id])->with('success', 'Pesan diperbarui.');
    }

    public function destroy(Request $request, int $message): RedirectResponse
    {
        $this->authorizeMember($request);
        $message = OpsMessage::whereHas('group', fn ($query) => $query->where('department', 'kbh'))->findOrFail($message);
        abort_unless($message->user_id === $request->user()->id, 403);
        $path = $message->file_path;
        $group = $message->group_id;
        $message->delete();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('kbh.forum', ['group' => $group])->with('success', 'Pesan dihapus.');
    }
}
