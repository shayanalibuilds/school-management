<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\AttendanceChart;
use App\Filament\Widgets\SchoolProgressChart;
use App\Filament\Widgets\SchoolProgressStats;
use App\Filament\Widgets\StudentPerformanceChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('dashboard')
            ->authGuard('admin')
            ->login()
            ->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): HtmlString => new HtmlString(
                    (string) view('filament.panel-styles'),
                ),
            )
            ->navigationGroups([
                'Academics',
                'People',
                'Finance',
                'Settings',
            ])
            ->colors([
                'primary' => [
                    50 => '#f1fdfb',
                    100 => '#e0faf4',
                    200 => '#c2f4e9',
                    300 => '#96eeda',
                    400 => '#4ff8d2',
                    500 => '#23e7ba',
                    600 => '#0f7861',
                    700 => '#13725d',
                    800 => '#135d4c',
                    900 => '#124e41',
                    950 => '#0a2f26',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\PaymentSettings::class,
                \App\Filament\Pages\AppSettingsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                SchoolProgressStats::class,
                StudentPerformanceChart::class,
                SchoolProgressChart::class,
                AttendanceChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->spa()
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
