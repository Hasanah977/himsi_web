<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiHimsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiHimsiController extends Controller
{
    public function destroyAll(Request $request): RedirectResponse
    {
        $request->validate(['confirmed' => ['required', 'accepted']]);
        NotifikasiHimsi::where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Semua notifikasi Anda telah dihapus.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        NotifikasiHimsi::where('user_id', $request->user()->id)->whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    public function destroy(Request $request, NotifikasiHimsi $notifikasi): RedirectResponse
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);
        $notifikasi->delete();

        return back()->with('success', 'Notifikasi dihapus.');
    }

    public function baca(Request $request, NotifikasiHimsi $notifikasi): RedirectResponse
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);
        if (! $notifikasi->dibaca_pada) {
            $notifikasi->update(['dibaca_pada' => now()]);
        }
        if ($request->boolean('mark_only')) {
            return back()->with('success', 'Notifikasi ditandai dibaca.');
        }
        $url = $notifikasi->url;
        if ($url && str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\')) {
            $user = $request->user();
            if ($user->canSwitchWorkspace()) {
                $segment = explode('/', ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/'))[0];
                if ($segment === 'kbh') {
                    $request->session()->put('workspace.'.$user->id, 'kbh');
                } elseif (in_array($segment, ['sekretaris', 'ketua-umum', 'bendahara', 'departemen', 'operasional', 'selamat', 'anggota'], true)) {
                    $request->session()->put('workspace.'.$user->id, $user->assignedRole());
                }
            }

            return redirect($url);
        }

        return back();
    }
}
