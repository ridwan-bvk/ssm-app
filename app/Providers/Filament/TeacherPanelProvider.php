<?php

namespace App\Providers\Filament;

use App\Filament\Teacher\Widgets\TeacherAbsenteeAlerts;
use App\Filament\Teacher\Widgets\TeacherClassOverview;
use App\Filament\Teacher\Widgets\TeacherTopLateStudents;
use App\Filament\Teacher\Widgets\TeacherTrendChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Mirrors the CI4 app's `teacher/*` route group (Teacher\Dashboard,
 * Teacher\Perizinan, Teacher\QRCode, Teacher\Reports controllers), gated by
 * the `guru` role the same way the old app gated it via `permission:teacher.access`.
 * A separate panel (not just resources bolted onto the admin one) keeps the
 * navigation and permission surface cleanly split, matching the old app's
 * separate route prefix.
 */
class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('teacher')
            ->brandName('Absensi Sekolah - Wali Kelas')
            ->login()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->spa()
            ->discoverPages(in: app_path('Filament/Teacher/Pages'), for: 'App\\Filament\\Teacher\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Teacher/Widgets'), for: 'App\\Filament\\Teacher\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                TeacherClassOverview::class,
                TeacherTrendChart::class,
                TeacherTopLateStudents::class,
                TeacherAbsenteeAlerts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
