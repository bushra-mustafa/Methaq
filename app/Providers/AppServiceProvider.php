<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Policies\AssetCollectionPolicy;
use App\Domains\Editor\Policies\TemplateAssetPolicy;
use App\Domains\Editor\Policies\TemplatePolicy;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Policies\EventPolicy;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Policies\OrderPolicy;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use App\Domains\Users\Policies\AuditLogPolicy;
use App\Domains\Users\Policies\UserPolicy;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Read from config (not env() in bootstrap) so a value in the .env file is honoured on shared hosting.
        $proxies = config('methaq.trusted_proxies');
        if (is_string($proxies) && $proxies !== '') {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        if (config('methaq.force_https')) {
            URL::forceScheme('https');
        }

        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(TemplateAsset::class, TemplateAssetPolicy::class);
        Gate::policy(AssetCollection::class, AssetCollectionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
