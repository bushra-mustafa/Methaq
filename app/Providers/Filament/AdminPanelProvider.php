<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Domains\Editor\Models\Template;
use App\Filament\Resources\Templates\TemplateResource;
use App\Http\Controllers\Admin\ShowPaymentReceiptController;
use App\Http\Middleware\SendAdministratorsToTwoFactorSetup;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Methaq administration panel at /admin.
 *
 * Sign-in stays with Fortify (no second login screen): guests are redirected to the
 * application's `login` route, and `User::canAccessPanel()` admits only active
 * administrators with confirmed two-factor authentication.
 */
final class AdminPanelProvider extends PanelProvider
{
    /** A banner on every admin page while an active template is hidden from customers for lacking a price. */
    private static function unpricedTemplatesNotice(): HtmlString
    {
        $hidden = Template::query()->where('is_active', true)->where('price_minor', 0)->count();

        if ($hidden === 0) {
            return new HtmlString('');
        }

        $link = e(TemplateResource::getUrl('index'));

        return new HtmlString(
            '<div role="status" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;margin-bottom:16px;border:1px solid #d9b779;border-radius:12px;padding:12px 16px;background:#fff7e6;color:#5c4a1f;font-size:14px;">'
            .'<strong>'.$hidden.' قالب مخفي عن العميلات لأنه بلا سعر.</strong>'
            .'<span>لا يظهر في المكتبة ولا في الرئيسية ولا عند إنشاء مناسبة حتى تحدّدي سعره.</span>'
            .'<a href="'.$link.'" style="margin-inline-start:auto;font-weight:700;text-decoration:underline;">تحديد الأسعار</a>'
            .'</div>',
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->darkMode(false)
            ->id('admin')
            ->path('admin')
            ->brandName('ميثاق · الإدارة')
            ->brandLogo(asset('brand/logos/methaq-horizontal-compact-flat.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('brand/logos/methaq-symbol-small-flat.svg'))
            ->font('Methaq Arabic', url: asset('css/admin-fonts.css'), provider: LocalFontProvider::class)
            ->colors([
                'primary' => Color::generateV3Palette('#3b5a3f'),
                'warning' => Color::generateV3Palette('#9a7b46'),
                'gray' => Color::Stone,
            ])
            ->renderHook(PanelsRenderHook::CONTENT_BEFORE, fn (): HtmlString => self::unpricedTemplatesNotice())
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="'.e(asset('css/admin-methaq.css')).'">'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ->authenticatedRoutes(function (): void {
                // Private receipts stream through the panel, never through a signed or public URL.
                Route::get('/receipts/{order}', ShowPaymentReceiptController::class)
                    ->whereNumber('order')
                    ->name('receipts.show');
            })
            ->authMiddleware([
                SendAdministratorsToTwoFactorSetup::class,
                Authenticate::class,
            ]);
    }
}
