<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Payments\Services\ManualPaymentEligibility;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CheckProductionEnvironmentCommand extends Command
{
    protected $signature = 'methaq:check-production-env';

    protected $description = 'Fail when settings that must be production-safe are local, unsafe or placeholders';

    public function handle(): int
    {
        $failures = $this->findFailures();

        if ($failures === []) {
            $this->info('Production-sensitive settings look safe.');

            return self::SUCCESS;
        }

        foreach ($failures as $setting => $reason) {
            $this->line("  - {$setting}: {$reason}");
        }

        $count = count($failures);
        $this->error("{$count} setting(s) need attention. Values are never printed.");

        return self::FAILURE;
    }

    /**
     * Maps each failing setting to the reason it fails. Values are never included, so the output is safe to keep in a deploy log.
     *
     * @return array<string, string>
     */
    private function findFailures(): array
    {
        $failures = [];

        if (config('app.env') !== 'production') {
            $failures['APP_ENV'] = 'must be production on the server.';
        }

        if (config('app.debug') !== false) {
            $failures['APP_DEBUG'] = 'must be false.';
        }

        if (blank(config('app.key'))) {
            $failures['APP_KEY'] = 'must be set.';
        }

        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $failures['APP_URL'] = 'must start with https://.';
        }

        if (config('methaq.force_https') !== true) {
            $failures['METHAQ_FORCE_HTTPS'] = 'must resolve to true.';
        }

        if (config('session.secure') !== true) {
            $failures['SESSION_SECURE_COOKIE'] = 'must resolve to true.';
        }

        if ($this->isReservedDomain((string) config('methaq.invitation_domain'))) {
            $failures['METHAQ_INVITATION_DOMAIN'] = 'must be the real invitation domain, not a test or placeholder domain.';
        }

        $fromAddress = (string) config('mail.from.address');

        if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false || $this->isReservedDomain(Str::after($fromAddress, '@'))) {
            $failures['MAIL_FROM_ADDRESS'] = 'must be a real address on your sending domain.';
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $failures['MAIL_MAILER'] = 'must send real mail, not log or array.';
        }

        if (in_array(config('logging.channels.single.level'), ['debug', 'info', 'notice'], true)) {
            $failures['LOG_LEVEL'] = 'must be warning or higher on the server.';
        }

        if (in_array('*', array_map('trim', explode(',', (string) config('methaq.trusted_proxies'))), true)) {
            $failures['TRUSTED_PROXIES'] = 'must list the proxy addresses, not *; * trusts any client that reaches the origin directly.';
        }

        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $sessionCookie = (string) config('session.cookie');
        $hostPrefixed = str_starts_with($sessionCookie, '__Host-');

        if ($this->sharesApex($appHost, (string) config('methaq.invitation_domain')) && ! $hostPrefixed) {
            $failures['SESSION_COOKIE'] = 'must start with __Host- when the invitation domain shares the app domain, so invitation subdomains cannot overwrite it.';
        }

        if ($hostPrefixed && filled(config('session.domain'))) {
            $failures['SESSION_DOMAIN'] = 'must be empty when SESSION_COOKIE uses the __Host- prefix.';
        }

        if ($this->hasTestBankLabel()) {
            $failures['METHAQ_BANK_*'] = 'still carries the test bank label; use the real account or leave the fields empty.';
        }

        return $failures;
    }

    private function hasTestBankLabel(): bool
    {
        return ManualPaymentEligibility::isTestLabelled(config('methaq.payment.bank_transfer.bank_name'))
            || ManualPaymentEligibility::isTestLabelled(config('methaq.payment.bank_transfer.account_name'));
    }

    private function sharesApex(string $host, string $otherHost): bool
    {
        return $host !== '' && $otherHost !== '' && $this->apex($host) === $this->apex($otherHost);
    }

    private function apex(string $host): string
    {
        return implode('.', array_slice(explode('.', strtolower(trim($host))), -2));
    }

    private function isReservedDomain(string $domain): bool
    {
        $labels = explode('.', strtolower(trim($domain)));

        return count($labels) < 2
            || in_array(end($labels), ['test', 'invalid', 'localhost', 'local'], true)
            || in_array('example', $labels, true);
    }
}
