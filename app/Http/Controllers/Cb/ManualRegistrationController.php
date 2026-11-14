<?php

namespace App\Http\Controllers\Cb;

use App\Http\Controllers\Controller;
use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ManualRegistrationController extends Controller
{
    public function create(): View
    {
        return view('cb.manual');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $rules = [
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'nim' => ['required', 'alpha_dash', 'max:30', 'unique:himsi_registrations,nim'],
            'angkatan' => ['required', 'integer', 'between:2000,'.now()->year],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
        ];
        foreach (HimsiRegistration::documents() as $key => $label) {
            $rules[$key] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }
        $data = $request->validate($rules);
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app/private/himsi-registration'), 'throw' => true]);
        $paths = [];
        try {
            foreach (HimsiRegistration::documents() as $key => $label) {
                $paths[$key.'_path'] = $request->hasFile($key) ? $disk->putFile('documents', $request->file($key)) : '';
            }
            $registration = DB::transaction(function () use ($data, $paths) {
                $user = new User;
                $user->forceFill(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'pending', 'is_active' => false])->save();
                $registration = HimsiRegistration::create(array_merge($paths, ['user_id' => $user->id, 'nim' => $data['nim'], 'angkatan' => $data['angkatan'], 'phone' => $data['phone'], 'program_studi' => 'Sistem Informasi', 'status' => 'pending']));
                DashboardController::record('Pendaftar KBH ditambahkan manual', $user->name.' · '.$data['nim']);

                return $registration;
            });
        } catch (\Throwable $exception) {
            $disk->delete(array_values(array_filter($paths)));
            throw $exception;
        }

        return redirect()->route('cb.recruitment.show', $registration)->with('success', 'Pendaftar ditambahkan. Akun menunggu keputusan seleksi dan belum aktif.');
    }
}
