<?php

namespace ErgoSarapu\DonationBundle\Tests\Functional;

use ErgoSarapu\DonationBundle\DonationBundle;
use ErgoSarapu\DonationBundle\Payum\PayumPaymentProvider;
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

        /** @var PayumPaymentProvider $provider */
        $provider = $kernel->getContainer()->get('donation_bundle.payum.payum_payment_provider');
        
        $this->assertNull($provider->getPaymentsConfig());
    }

    public function testPaymentsConfigEmpty(): void{
        $kernel = new DonationBundleTestingKernel(['payments' => []]);
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }

    public function testPaymentsConfigInvalidBankCountry(): void{
        $kernel = new DonationBundleTestingKernel('invalid_bank_country_code');
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/.*(Not a valid alpha-2 country code)/');
        $kernel->boot();
    }

    public function testPaymentsConfigFull(): void{
        $kernel = new DonationBundleTestingKernel('full');
        $kernel->boot();

        /** @var PayumPaymentProvider $provider */
        $provider = $kernel->getContainer()->get('donation_bundle.payum.payum_payment_provider');

        $this->assertNotNull($provider->getPaymentsConfig());
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