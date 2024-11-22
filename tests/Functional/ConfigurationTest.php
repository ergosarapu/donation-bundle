<?php

namespace ErgoSarapu\DonationBundle\Tests\Functional;

use ErgoSarapu\DonationBundle\DonationBundle;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ConfigurationTest extends TestCase
{
    public function testEmptyConfiguration(): void{
        $kernel = new DonationBundleTestingKernel();
        $kernel->boot();

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = $kernel->getContainer()->get('donation_bundle.form.form_options_provider');
        
        $this->assertEmpty($optionsProvider->getGateways());
        $this->assertEmpty($optionsProvider->getCurrencies());
    }

    public function testGatewaysConfigEmpty(): void{
        $kernel = new DonationBundleTestingKernel(['gateways' => []]);
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }

    public function testFormConfigEmpty(): void{
        $kernel = new DonationBundleTestingKernel(['form' => []]);
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }

    public function testGatewayConfigInvalidBankCountry(): void{
        $kernel = new DonationBundleTestingKernel('gateway_invalid_country_code');
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Not a valid alpha-2 country code)/');
        $kernel->boot();
    }

    public function testFormConfigInvalidCurrency(): void{
        $kernel = new DonationBundleTestingKernel('form_invalid_currency_code');
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Not a valid currency code.)/');
        $kernel->boot();
    }

    public function testGatewayConfigInvalidFrequencyDateInterval(): void{
        $kernel = new DonationBundleTestingKernel('gateway_invalid_frequency_date_interval');
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Invalid frequency date interval format)/');
        $kernel->boot();
    }

    public function testGatewayConfigMissingFrequency(): void{
        $kernel = new DonationBundleTestingKernel('gateway_missing_frequency_date_interval');
        $kernel->boot();

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = $kernel->getContainer()->get('donation_bundle.form.form_options_provider');

        $this->assertNotEmpty($optionsProvider->getGateways());
    }

    public function testFullConfig(): void{
        $kernel = new DonationBundleTestingKernel('full');
        $kernel->boot();

        /** @var FormOptionsProvider $optionsProvider */
        $optionsProvider = $kernel->getContainer()->get('donation_bundle.form.form_options_provider');

        $this->assertNotEmpty($optionsProvider->getGateways());
        $this->assertNotEmpty($optionsProvider->getCurrencies());
    }
}

class DonationBundleTestingKernel extends Kernel
{
    public function __construct(private array|string|null $config = null)
    {
        parent::__construct($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
    }

    public function registerBundles(): iterable {
        return [
            new DonationBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void {
        if ($this->config === null) {
            return;
        }
        if (is_string($this->config)) {
            $loader->load(__DIR__.'/Fixtures/config/'.$this->config.'.yaml', 'yaml');
            return;
        }
        if (is_array($this->config)) {
            $loader->load(function(ContainerBuilder $container){
                $container->loadFromExtension('donation', $this->config);
            });
            return;
        }
        throw new InvalidArgumentException('Unsupported config type');
    }
    
    public function getCacheDir(): string
    {
        // Ensure each kernel instance generates its own cache allowing different test cases do not reuse the cache
        return parent::getCacheDir().'/'.spl_object_hash($this);
    }
}