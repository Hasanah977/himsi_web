<?php

namespace App\Support;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;

class SecurityAudit
{
    public static function record(string $event, string $summary, string $severity = 'info', array $context = [], int $affected = 0, ?User $actor = null): SecurityEvent
    {
        $actor ??= request()->user();

        return SecurityEvent::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor?->role,
            'event' => $event,
            'severity' => $severity,
            'summary' => $summary,
            'ip_address' => request()->ip(),
            'route' => request()->route()?->getName(),
            'affected_count' => $affected,
            'context' => $context,
        ]);
    }

    public static function failedLogin(Failed $event): void
    {
        $email = mb_strtolower(trim((string) ($event->credentials['email'] ?? '')));
        $key = 'security.login.'.hash('sha256', $email);
        Cache::add($key, 0, now()->addMinutes(30));
        $attempts = Cache::increment($key);
        $user = $event->user instanceof User ? $event->user : User::where('email', $email)->first();
        self::record('login_failed', $attempts >= 3 ? 'Tiga atau lebih kegagalan login berturut-turut.' : 'Percobaan login gagal.', $attempts >= 3 ? 'danger' : 'warning', ['attempts' => $attempts, 'account_fingerprint' => hash('sha256', $email)], actor: $user);
    }

    public static function loggedIn(Login $event): void
    {
        Cache::forget('security.login.'.hash('sha256', mb_strtolower(trim($event->user->email))));
        if (request()->hasSession()) {
            request()->session()->put('security_generation', app(EmergencyState::class)->read()['generation']);
        }
        self::record('login', 'Login berhasil.', actor: $event->user);
    }

    public static function deletion(QueryExecuted $query): void
    {
        if (! request()->attributes->get('security_audit_active') || ! request()->hasSession() || request()->isMethodSafe() || request()->attributes->get('security_recovery') || ! preg_match('/^delete\s+from\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', ltrim($query->sql), $matches)) {
            return;
        }
        $table = $matches[1];
        if (in_array($table, ['security_events', 'sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'migrations', 'jobs', 'failed_jobs', 'job_batches'], true)) {
            return;
        }
        $driver = $query->connection->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            return;
        }
        $affected = (int) $query->connection->getPdo()->query($driver === 'mysql' ? 'SELECT ROW_COUNT()' : 'SELECT changes()')->fetchColumn();
        $user = request()->user();
        if (! $user || $affected < 1) {
            return;
        }
        $recent = SecurityEvent::where('actor_id', $user->id)->where('event', 'data_deleted')->where('created_at', '>=', now()->subMinutes(5))->sum('affected_count');
        $danger = $recent + $affected >= config('security.bulk_delete_threshold');
        self::record('data_deleted', $danger ? 'Penghapusan massal / berulang terdeteksi.' : 'Data dihapus.', $danger ? 'danger' : 'warning', ['table' => $table, 'five_minute_total' => $recent + $affected], $affected, $user);
    }
}
