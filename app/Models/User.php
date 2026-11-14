<?php

namespace App\Models;

use App\Support\Governance;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const OFFICER_ROLES = [
        'ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->isDirty('role') && in_array($user->assignedRole(), self::OFFICER_ROLES, true)) {
                Governance::assertWritable();
            }
        });
    }

    public function isStudentAccount(): bool
    {
        return in_array($this->assignedRole(), ['kbh', ...self::OFFICER_ROLES], true);
    }

    public function canSwitchWorkspace(): bool
    {
        return $this->is_active && in_array($this->attributes['role'] ?? null, [
            'ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen',
        ], true);
    }

    public function assignedRole(): string
    {
        return $this->attributes['role'] ?? 'pending';
    }

    public function getRoleAttribute(?string $value): ?string
    {
        if ($this->canSwitchWorkspace() && request()->hasSession()
            && request()->session()->get('workspace.'.$this->id) === 'kbh') {
            return 'kbh';
        }

        return $value;
    }

    public function encryptedAccountId(): string
    {
        return Crypt::encryptString((string) $this->id);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_active
            && $this->role === 'super_admin';
    }

    public function awaitingRegistration(): bool
    {
        return in_array($this->role, ['pending', 'kbh'], true)
            && $this->registration !== null
            && $this->registration->status !== 'approved';
    }

    public function getAdministrationRoleAttribute(): ?string
    {
        $role = $this->role;
        if ($role === 'super_admin' && request()->isMethodSafe()) {
            $role = request()->attributes->get('administration_preview_role', $role);
        }

        return ['kbh' => 'KBH', 'ketua_umum' => 'Ketua Himpunan', 'sekretaris_umum' => 'Sekertaris', 'bendahara_umum' => 'Bendahara', 'koordinator_pengawas' => 'Koordinator Pengawas', 'koordinator_departemen' => 'Koordinator Departemen'][$role] ?? null;
    }

    public function registration(): HasOne
    {
        return $this->hasOne(HimsiRegistration::class);
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->name;
    }

    public function getStatusAkunAttribute(): string
    {
        return $this->is_active ? 'aktif' : 'nonaktif';
    }

    public function getNimAttribute(): ?string
    {
        return $this->registration?->nim;
    }

    public function getNoHpAttribute(): ?string
    {
        return $this->phone ?? $this->registration?->phone;
    }

    public function getAngkatanKelasAttribute(): ?string
    {
        return $this->registration?->angkatan;
    }
}
