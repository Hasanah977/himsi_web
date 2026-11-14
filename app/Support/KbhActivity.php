<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class KbhActivity
{
    public static function recordVisit(Request $request, Carbon $visitedAt): void
    {
        if ($request->routeIs('kbh.voting.*')) {
            DB::table('kbh_activity_reads')->updateOrInsert(['user_id' => $request->user()->id, 'route' => 'kbh.voting.hasil'], ['seen_at' => $visitedAt]);

            return;
        }
        foreach (config('administration.menus.kbh', []) as $item) {
            $path = route($item[1], [], false);
            if ('/'.$request->path() === $path || str_starts_with('/'.$request->path(), $path.'/')) {
                DB::table('kbh_activity_reads')->updateOrInsert(['user_id' => $request->user()->id, 'route' => $item[1]], ['seen_at' => $visitedAt]);
            }
        }
    }
}
