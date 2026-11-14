<?php

namespace App\Http\Controllers\kbh;

use App\Http\Controllers\Controller;
use App\Support\MemberProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilController extends Controller
{
    public function index(): View
    {
        return view('kbh.profil.index', ['appointments' => MemberProfile::appointments(auth()->user())]);
    }

    public function edit(): View
    {
        return view('kbh.profil.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'no_hp' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'foto_profile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $path = $request->file('foto_profile')?->store('kbh/profiles', 'local');
        $oldPath = $user->foto_profile;
        try {
            DB::transaction(function () use ($user, $validated, $path): void {
                $changes = ['email' => $validated['email'], 'phone' => $validated['no_hp']];
                if ($user->email !== $validated['email']) {
                    $changes['email_verified_at'] = null;
                }
                if ($path) {
                    $changes['foto_profile'] = $path;
                }
                $user->forceFill($changes)->save();
                $user->registration()->update(['phone' => $validated['no_hp']]);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($path && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()
            ->route('kbh.profil')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate(['foto_profile' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $user = $request->user();
        $oldPath = $user->foto_profile;
        $path = $request->file('foto_profile')->store('kbh/profiles', 'local');
        try {
            $user->forceFill(['foto_profile' => $path])->save();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function photo(Request $request): StreamedResponse
    {
        $path = $request->user()->foto_profile;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function password(): View
    {
        return view('kbh.profil.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_lama' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers(),
                'confirmed',
            ],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('kbh.profil')
            ->with('success', 'Password berhasil diubah.');
    }
}
