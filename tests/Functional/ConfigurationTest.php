<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Functional;

use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleKernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurationTest extends DonationBundleKernelTestCase
{
    public function testEmptyConfiguration(): void
    {
        self::bootkernelWithConfig(null);

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = self::getContainer()->get('donation_bundle.form.form_options_provider');

        $this->assertEmpty($optionsProvider->getGateways());
        $this->assertEmpty($optionsProvider->getCurrencies());
    }

    public function testGatewaysConfigEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        self::bootkernelWithConfig(['gateways' => []]);
    }

    public function testFormConfigEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        self::bootkernelWithConfig(['form' => []]);

    }

    public function testGatewayConfigInvalidBankCountry(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Not a valid alpha-2 country code)/');
        self::bootkernelWithConfig(__DIR__.'/Fixtures/config/gateway_invalid_country_code.yaml');

    }

    public function testFormConfigInvalidCurrency(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Not a valid currency code.)/');
        self::bootkernelWithConfig(__DIR__.'/Fixtures/config/form_invalid_currency_code.yaml');

    }

    public function testGatewayConfigInvalidFrequencyDateInterval(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Invalid frequency date interval format)/');
        self::bootkernelWithConfig(__DIR__.'/Fixtures/config/gateway_invalid_frequency_date_interval.yaml');
    }

    public function testGatewayConfigMissingFrequency(): void
    {
        self::bootkernelWithConfig(__DIR__.'/Fixtures/config/gateway_missing_frequency_date_interval.yaml');

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = self::getContainer()->get('donation_bundle.form.form_options_provider');
        $this->assertNotEmpty($optionsProvider->getGateways());
    }

    public function testFullConfig(): void
    {
        self::bootkernelWithConfig(__DIR__.'/Fixtures/config/full.yaml');

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = self::getContainer()->get('donation_bundle.form.form_options_provider');

        $this->assertNotEmpty($optionsProvider->getGateways());
        $this->assertNotEmpty($optionsProvider->getCurrencies());
    }

    /**
     * @param array<string,mixed>|string|null $bundleConfig
     */
    private static function bootKernelWithConfig(array|string|null $bundleConfig = null): void
    {
        self::bootKernel([
            'bundle_config' => $bundleConfig
        ]);
    }
}
