<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\PublicContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request, string $department): View
    {
        $categories = config('superadmin.sections.announcements.fields.category.2');
        $request->validate(['category' => ['nullable', Rule::in($categories)], 'q' => ['nullable', 'string', 'max:120']]);
        $query = PublicContent::announcementsFor($department);
        $reads = DB::table('announcement_reads')->where('user_id', $request->user()->id)->select('content_id');
        $unread = (clone $query)->whereNotIn('id', $reads)->count();
        $items = $query->when($request->filled('category'), fn (Builder $query) => $query->where('payload->category', $request->input('category')))
            ->when($request->filled('q'), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('payload->title', 'like', '%'.$request->input('q').'%')->orWhere('payload->summary', 'like', '%'.$request->input('q').'%')))
            ->latest()->paginate(12)->withQueryString();
        $readIds = (clone $reads)->whereIn('content_id', $items->pluck('id'))->pluck('content_id')->all();

        return view('department.member-announcements', compact('items', 'categories', 'unread', 'readIds'));
    }

    public function show(Request $request, string $department, int $content): View
    {
        $content = PublicContent::announcementsFor($department)->findOrFail($content);
        DB::table('announcement_reads')->insertOrIgnore(['content_id' => $content->id, 'user_id' => $request->user()->id, 'read_at' => now()]);

        return view('department.member-announcement', compact('content'));
    }
}
