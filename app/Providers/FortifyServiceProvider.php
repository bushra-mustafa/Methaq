<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Users\Actions\AuthenticateUserAction;
use App\Domains\Users\Actions\CreateNewUserAction;
use App\Domains\Users\Actions\ResetUserPasswordAction;
use App\Domains\Users\Actions\UpdateUserPasswordAction;
use App\Domains\Users\Actions\UpdateUserProfileAction;
use App\Http\Responses\PasswordResetLinkResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUserAction::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileAction::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPasswordAction::class);
        Fortify::resetUserPasswordsUsing(ResetUserPasswordAction::class);
        Fortify::authenticateUsing($this->app->make(AuthenticateUserAction::class));
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        $this->configureViews();
        $this->configureRateLimits();
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request): Response => Inertia::render('Auth/Login', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::registerView(fn (): Response => Inertia::render('Auth/Register'));
        Fortify::requestPasswordResetLinkView(fn (Request $request): Response => Inertia::render('Auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::resetPasswordView(fn (Request $request): Response => Inertia::render('Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => (string) $request->route('token'),
        ]));
        Fortify::verifyEmailView(fn (Request $request): Response => Inertia::render('Auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::confirmPasswordView(fn (): Response => Inertia::render('Auth/ConfirmPassword'));
        Fortify::twoFactorChallengeView(fn (): Response => Inertia::render('Auth/TwoFactorChallenge'));
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower($request->string(Fortify::username())->toString());

            return Limit::perMinute(5)->by(Str::transliterate($email).'|'.$request->ip());
        });
        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)
            ->by((string) $request->session()->get('login.id').'|'.$request->ip()));
    }
}
