<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmergencyState;
use App\Support\SecurityAudit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class HimsiAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $credentials = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Masukkan alamat email yang valid.',
                'password.required' => 'Kata sandi wajib diisi.',
            ]
        );

        if (app(EmergencyState::class)->read()['frozen'] && ! User::where('email', $credentials['email'])->where('role', 'super_admin')->where('is_active', true)->exists()) {
            SecurityAudit::record('login_blocked', 'Login diblokir selama pembekuan akses.', 'warning');

            return back()->withErrors(['email' => 'Akses akun sementara dibekukan oleh Super Admin.'])->onlyInput('email');
        }

        if (! Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            return back()
                ->withErrors([
                    'email' => 'Email atau kata sandi salah, atau akun sedang dinonaktifkan.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()->awaitingRegistration()) {
            return redirect()->route('registration.review');
        }
        if (! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Akun Anda sedang dinonaktifkan.'])->onlyInput('email');
        }

        if (RoleWelcomeController::pending($request->user())) {
            return redirect()->route('role.welcome');
        }

        // Route dashboard menentukan tujuan berdasarkan role.
        return redirect()->route('dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /*
    |--------------------------------------------------------------------------
    | KIRIM TAUTAN RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function forgot(Request $request)
    {
        $request->validate(
            [
                'email' => ['required', 'email'],
            ],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Masukkan alamat email yang valid.',
            ]
        );

        Password::sendResetLink(
            $request->only('email')
        );

        return back()->with(
            'status',
            'Jika email terdaftar dan dapat diproses, tautan reset akan dikirim. Periksa kotak masuk dan folder spam.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN FORM RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function resetForm(Request $request, string $token)
    {
        return view('himsi', [
            'page' => 'reset',
            'title' => 'Atur Ulang Kata Sandi',
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PASSWORD BARU
    |--------------------------------------------------------------------------
    */

    public function reset(Request $request)
    {
        $request->validate(
            [
                'token' => ['required', 'string'],
                'email' => ['required', 'email'],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    PasswordRule::min(8),
                ],
            ],
            [
                'token.required' => 'Token reset tidak ditemukan.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Masukkan alamat email yang valid.',
                'password.required' => 'Kata sandi baru wajib diisi.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            ]
        );

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Kata sandi berhasil diperbarui. Silakan login.'
                );
        }

        return back()
            ->withErrors([
                'email' => 'Tautan reset tidak valid atau kedaluwarsa. Silakan minta tautan baru.',
            ])
            ->onlyInput('email');
    }
}
