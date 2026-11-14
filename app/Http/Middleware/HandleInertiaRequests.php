<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\ContactChannels;
use App\Http\Support\PageMeta;
use App\Http\Support\TurnstileVerifier;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user instanceof User && $user->isActive() ? [
                    'id' => (string) $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'emailVerified' => $user->hasVerifiedEmail(),
                ] : null,
            ],
            'turnstile' => ($siteKey = app(TurnstileVerifier::class)->siteKey()) === null ? null : ['siteKey' => $siteKey],
            'analytics' => [
                'enabled' => (bool) config('methaq.analytics.enabled') && filled(config('methaq.analytics.domain')),
                'domain' => config('methaq.analytics.domain'),
                'script' => config('methaq.analytics.script'),
                'exclude' => config('methaq.analytics.exclude'),
            ],
            'meta' => PageMeta::make('', PageMeta::DEFAULT_DESCRIPTION, null, null, true),
            'contactChannels' => fn (): array => app(ContactChannels::class)->active(),
            'support' => fn (): ?array => $user instanceof User && $user->isActive() && $request->is('app', 'app/*')
                ? ['unread' => SupportThread::query()->where('user_id', $user->getKey())->where('customer_unread', true)->count()]
                : null,
            'flash' => [
                'status' => fn (): ?string => $request->session()->get('status'),
            ],
        ];
    }
}
