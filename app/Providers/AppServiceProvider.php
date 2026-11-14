<?php

namespace App\Providers;

use App\Models\FinanceEntry;
use App\Models\HimsiRegistration;
use App\Models\KbhAspirasi;
use App\Models\KbhIuranPembayaran;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use App\Models\OpsMessage;
use App\Models\OpsSubmission;
use App\Models\PprkSubmission;
use App\Models\PublicContent;
use App\Support\EmergencyState;
use App\Support\SecurityAudit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Failed::class, [SecurityAudit::class, 'failedLogin']);
        Event::listen(Login::class, [SecurityAudit::class, 'loggedIn']);
        DB::listen(SecurityAudit::deletion(...));
        \Illuminate\Support\Facades\View::composer('components.sidebar-account', function (View $view): void {
            $view->with('sidebarUnreadUrls', Schema::hasTable((new NotifikasiHimsi)->getTable())
                ? NotifikasiHimsi::where('user_id', auth()->id())->whereNull('dibaca_pada')->whereNotNull('url')->pluck('url')->unique()->values()
                : collect());
        });
        \Illuminate\Support\Facades\View::composer('components.chair-registration', function (View $view): void {
            $view->with('chairElection', KpumElection::find(1));
            $view->with('chairCandidate', KpumCandidate::where('election_id', 1)->where('user_id', auth()->id())->first());
        });
        \Illuminate\Support\Facades\View::composer('admin.layout', function (View $view): void {
            $view->with('emergencyState', app(EmergencyState::class)->read());
        });
        \Illuminate\Support\Facades\View::composer('components.notification-bell', function (View $view): void {
            if (request()->routeIs('admin.security.*') && ! Schema::hasTable((new NotifikasiHimsi)->getTable())) {
                $view->with('headerUnreadCount', 0);

                return;
            }
            $view->with('headerUnreadCount', NotifikasiHimsi::where('user_id', auth()->id())->whereNull('dibaca_pada')->count());
        });
        \Illuminate\Support\Facades\View::composer('administration.shell', function (View $view): void {
            $user = auth()->user();
            $unread = NotifikasiHimsi::where('user_id', $user->id)->whereNull('dibaca_pada')->get(['url']);
            $activityRoutes = [];
            foreach (config('administration.menus.kbh', []) as $item) {
                $path = route($item[1], [], false);
                $activityRoutes[$item[1]] = $unread->contains(fn ($notification) => $notification->url && str_starts_with(parse_url($notification->url, PHP_URL_PATH) ?? '', $path));
            }
            if ($user->role === 'kbh') {
                $seen = DB::table('kbh_activity_reads')->where('user_id', $user->id)->pluck('seen_at', 'route');
                $sources = [
                    'kbh.pengumuman.index' => PublicContent::where('section', 'announcements')->where('is_published', true)->whereNull('department')->max('updated_at'),
                    'kbh.program-kerja.index' => PublicContent::where('section', 'programs')->where('is_published', true)->whereNull('department')->max('updated_at'),
                    'kbh.voting.hasil' => KpumElection::where('status', '!=', 'draft')->max('updated_at'),
                    'kbh.iuran.index' => KbhIuranPembayaran::where('user_id', $user->id)->max('updated_at'),
                    'kbh.aspirasi.index' => KbhAspirasi::where('user_id', $user->id)->max('updated_at'),
                    'kbh.seleksi.index' => KbhPendaftaranSeleksi::where('user_id', $user->id)->max('updated_at'),
                ];
                foreach ($sources as $route => $updatedAt) {
                    $activityRoutes[$route] = ($activityRoutes[$route] ?? false) || ($updatedAt && (! isset($seen[$route]) || $updatedAt > $seen[$route]));
                }
            }
            $activityRoutes['kbh.forum'] = OpsMessage::whereHas('group', fn ($query) => $query->where('department', 'kbh'))->where('user_id', '!=', $user->id)->whereRaw('ops_messages.id > COALESCE((SELECT last_message_id FROM ops_reads WHERE ops_reads.group_id = ops_messages.group_id AND ops_reads.user_id = ?), 0)', [$user->id])->exists();
            $financeActivity = [];
            if ($user->role === 'bendahara_umum' || request()->attributes->get('administration_preview_role') === 'bendahara_umum') {
                $financeSources = [
                    'finance.entries:income' => FinanceEntry::where('kind', 'income')->max('updated_at'),
                    'finance.entries:expense' => FinanceEntry::where('kind', 'expense')->max('updated_at'),
                    'finance.billing' => KbhIuranPembayaran::max('updated_at'),
                    'finance.funding' => OpsSubmission::where('type', 'budget')->where('status', '!=', 'draft')->max('updated_at'),
                    'finance.report' => FinanceEntry::max('updated_at'),
                    'finance.monitoring' => FinanceEntry::whereNotNull('submission_id')->max('updated_at'),
                    'finance.logs' => DB::table('finance_audits')->max('id'),
                ];
                foreach ($financeSources as $key => $marker) {
                    $sessionKey = 'finance_seen.'.$user->id.'.'.str_replace('.', '_', $key);
                    $current = request()->route()?->getName().(request()->route('kind') ? ':'.request()->route('kind') : '');
                    if ($current === $key) {
                        request()->session()->put($sessionKey, (string) $marker);
                    }
                    $financeActivity[$key] = $marker !== null && request()->session()->get($sessionKey) !== (string) $marker;
                }
            }
            $view->with('financeActivity', $financeActivity);
            $view->with(['unreadCount' => $unread->count(), 'activityRoutes' => $activityRoutes]);
        });
        \Illuminate\Support\Facades\View::composer('department.*', function (View $view): void {
            $request = request();
            if (! $request->attributes->has('department_view_data')) {
                $department = $request->route('department') ?? (in_array(auth()->user()?->role, ['koordinator_departemen', 'anggota_departemen'], true) ? auth()->user()->department : null);
                $user = auth()->user();
                $settings = PublicContent::settings();
                $memberPortal = $user && ($user->role === 'anggota_departemen' || $request->routeIs('department.member.*', 'department.my-tasks') || $request->query('portal') === 'member');
                $request->attributes->set('department_view_data', [
                    'departmentKey' => $department,
                    'departmentLabel' => $department === 'umum' ? 'Forum HIMSI' : (config('superadmin.departments')[$department] ?? 'Tinjauan Operasional'),
                    'departmentLogo' => PublicContent::imageUrl($settings['logo'] ?? 'logo-himsi.png'),
                    'pprkApproved' => $user?->role === 'koordinator_departemen' && PprkSubmission::approvedFor($user->department, config('department.period')),
                    'isCoordinator' => ! $memberPortal && $user && ($user->isSuperAdmin() || $user->role === 'koordinator_departemen'),
                    'isMemberPortal' => $memberPortal,
                    'unreadNotifications' => $user ? NotifikasiHimsi::where('user_id', $user->id)->whereNull('dibaca_pada')->count() : 0,
                    'templateAvailable' => Storage::disk('local')->exists(config('department.lpj_template')),
                ]);
            }
            $view->with($request->attributes->get('department_view_data'));
        });
        \Illuminate\Support\Facades\View::composer('cb.layout', function (View $view): void {
            $settings = PublicContent::settings();
            $view->with('cbLogo', PublicContent::imageUrl($settings['logo'] ?? 'logo-himsi.png'));
            $view->with('cbPending', HimsiRegistration::where('status', 'pending')->count());
        });
        \Illuminate\Support\Facades\View::composer('kpum.layout', function (View $view): void {
            $settings = PublicContent::settings();
            $view->with('kpumLogo', PublicContent::imageUrl($settings['logo'] ?? 'logo-himsi.png'));
        });
    }
}
