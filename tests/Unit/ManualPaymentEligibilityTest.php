<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Payments\Services\ManualPaymentEligibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ManualPaymentEligibilityTest extends TestCase
{
    #[DataProvider('labelledValues')]
    public function test_a_test_label_in_arabic_or_english_is_recognised(string $value): void
    {
        $this->assertTrue(ManualPaymentEligibility::isTestLabelled($value));
    }

    #[DataProvider('realLabelledValues')]
    public function test_a_real_bank_name_is_not_mistaken_for_a_test_label(string $value): void
    {
        $this->assertFalse(ManualPaymentEligibility::isTestLabelled($value));
    }

    public function test_a_test_labelled_settings_account_is_refused_only_in_production(): void
    {
        $testAccount = ['bank_name' => 'مصرف الوحدة (تجريبي)', 'account_name' => 'ميثاق للتجربة'];

        $this->assertFalse(ManualPaymentEligibility::mayOfferSettingsAccount($testAccount, 'production'));
        $this->assertTrue(ManualPaymentEligibility::mayOfferSettingsAccount($testAccount, 'local'));
    }

    public function test_a_real_settings_account_is_offered_in_production(): void
    {
        $realAccount = ['bank_name' => 'مصرف الجمهورية', 'account_name' => 'ميثاق'];

        $this->assertTrue(ManualPaymentEligibility::mayOfferSettingsAccount($realAccount, 'production'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function labelledValues(): array
    {
        return [
            'Arabic test marker' => ['مصرف الوحدة (تجريبي)'],
            'Arabic trial word' => ['ميثاق للتجربة'],
            'English demo word' => ['Demo Bank'],
            'English test word' => ['Test account'],
            'English fake word' => ['FAKE BANK'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function realLabelledValues(): array
    {
        return [
            'Arabic bank name' => ['مصرف الجمهورية'],
            'Arabic account holder' => ['ميثاق'],
            'English word that contains test' => ['Contest Bank'],
        ];
    }
}
