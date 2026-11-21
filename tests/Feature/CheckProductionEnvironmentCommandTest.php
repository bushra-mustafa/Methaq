<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CheckProductionEnvironmentCommandTest extends TestCase
{
    public function test_safe_production_settings_pass_the_check(): void
    {
        config($this->safeProductionSettings());

        $this->artisan('methaq:check-production-env')->assertSuccessful();
    }

    #[DataProvider('unsafeProductionSettings')]
    public function test_unsafe_setting_fails_the_check_and_is_named_in_the_output(string $configKey, mixed $unsafeValue, string $settingName): void
    {
        config($this->safeProductionSettings());
        config([$configKey => $unsafeValue]);

        $this->artisan('methaq:check-production-env')
            ->expectsOutputToContain($settingName)
            ->assertFailed();
    }

    public function test_every_unsafe_setting_is_listed_in_one_run(): void
    {
        config($this->safeProductionSettings());
        config(['app.debug' => true, 'session.secure' => false]);

        $this->artisan('methaq:check-production-env')
            ->expectsOutputToContain('APP_DEBUG')
            ->expectsOutputToContain('SESSION_SECURE_COOKIE')
            ->doesntExpectOutputToContain('base64:test-key-for-the-check')
            ->assertFailed();
    }

    public function test_bank_details_with_the_test_label_fail_and_the_bank_name_is_never_printed(): void
    {
        config($this->safeProductionSettings());
        config(['methaq.payment.bank_transfer.bank_name' => 'مصرف الاختبار (تجريبي)']);

        $this->artisan('methaq:check-production-env')
            ->expectsOutputToContain('METHAQ_BANK_*')
            ->doesntExpectOutputToContain('مصرف الاختبار')
            ->assertFailed();
    }

    /**
     * @return array<string, array{0: string, 1: mixed, 2: string}>
     */
    public static function unsafeProductionSettings(): array
    {
        return [
            'APP_ENV is not production' => ['app.env', 'local', 'APP_ENV'],
            'APP_DEBUG is on' => ['app.debug', true, 'APP_DEBUG'],
            'APP_KEY is missing' => ['app.key', '', 'APP_KEY'],
            'APP_URL is plain http' => ['app.url', 'http://methaq-check.net', 'APP_URL'],
            'HTTPS enforcement is off' => ['methaq.force_https', false, 'METHAQ_FORCE_HTTPS'],
            'secure session cookies are off' => ['session.secure', false, 'SESSION_SECURE_COOKIE'],
            'invitation domain is the test default' => ['methaq.invitation_domain', 'methaq.test', 'METHAQ_INVITATION_DOMAIN'],
            'mail sender uses a reserved domain' => ['mail.from.address', 'hello@example.com', 'MAIL_FROM_ADDRESS'],
            'mail is written to the log' => ['mail.default', 'log', 'MAIL_MAILER'],
            'log level is debug' => ['logging.channels.single.level', 'debug', 'LOG_LEVEL'],
            'session cookie lacks the host prefix' => ['session.cookie', 'methaq_app_session', 'SESSION_COOKIE'],
            'session domain is set with the host prefix' => ['session.domain', 'methaq-check.net', 'SESSION_DOMAIN'],
            'trusted proxies trust every client' => ['methaq.trusted_proxies', '*', 'TRUSTED_PROXIES'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function safeProductionSettings(): array
    {
        return [
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:test-key-for-the-check',
            'app.url' => 'https://methaq-check.net',
            'methaq.force_https' => true,
            'session.secure' => true,
            'methaq.invitation_domain' => 'methaq-check.net',
            'mail.default' => 'smtp',
            'mail.from.address' => 'support@methaq-check.net',
            'logging.channels.single.level' => 'warning',
            'session.cookie' => '__Host-methaq_app_session',
            'session.domain' => null,
            'methaq.trusted_proxies' => '',
            'methaq.payment.bank_transfer.bank_name' => '',
            'methaq.payment.bank_transfer.account_name' => '',
            'methaq.payment.bank_transfer.account_number' => '',
            'methaq.payment.bank_transfer.iban' => '',
        ];
    }
}
