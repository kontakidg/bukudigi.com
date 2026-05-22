<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('bukudigi')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\PlatformStatsOverview::class,
                \App\Filament\Widgets\TrendsChart::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        /* Topbar background slate-900 (samakan dengan footer homepage) */
                        .fi-topbar > nav {
                            background-color: #0f172a !important;
                            border-bottom-color: #1e293b !important;
                        }
                        .fi-topbar > nav .fi-logo,
                        .fi-topbar > nav button,
                        .fi-topbar > nav a {
                            color: #f1f5f9 !important;
                        }
                        .fi-topbar > nav button:hover,
                        .fi-topbar > nav a:hover {
                            color: #ffffff !important;
                        }
                        .fi-topbar > nav .fi-icon-btn {
                            color: #cbd5e1 !important;
                        }
                        /* Brand image — hilangkan text "bukudigi" karena logo sudah punya teks */
                        .fi-topbar .fi-logo span,
                        .fi-sidebar-header .fi-logo span {
                            display: none !important;
                        }
                        .fi-topbar .fi-logo img,
                        .fi-sidebar-header .fi-logo img {
                            filter: brightness(1) !important;
                        }

                        /* Sidebar header (area logo pojok kiri atas) — samakan dengan topbar */
                        .fi-sidebar-header,
                        aside.fi-sidebar > header,
                        aside.fi-sidebar > div:first-child,
                        .fi-sidebar > .fi-sidebar-header,
                        [class*="fi-sidebar-header"] {
                            background-color: #0f172a !important;
                            background: #0f172a !important;
                            border-bottom: 1px solid #1e293b !important;
                            border-right: 1px solid #1e293b !important;
                            box-shadow: none !important;
                        }
                        .fi-sidebar-header *,
                        aside.fi-sidebar > header * {
                            background-color: transparent !important;
                        }
                        .fi-sidebar-header .fi-icon-btn,
                        .fi-sidebar-header button,
                        .fi-sidebar-header a,
                        aside.fi-sidebar > header button,
                        aside.fi-sidebar > header a {
                            color: #cbd5e1 !important;
                        }
                        .fi-sidebar-header .fi-icon-btn:hover,
                        .fi-sidebar-header button:hover,
                        .fi-sidebar-header a:hover,
                        aside.fi-sidebar > header button:hover {
                            color: #ffffff !important;
                        }
                        /* Container/wrapper di atas yang mungkin punya bg putih */
                        .fi-layout > header,
                        .fi-main-ctn > header,
                        .fi-sidebar {
                            border-right-color: #1e293b !important;
                        }
                    </style>
                HTML)
            )
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
