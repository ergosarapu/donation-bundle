<?php

namespace ErgoSarapu\DonationBundle\Tests\Functional;

use ErgoSarapu\DonationBundle\DonationBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ConfigurationTest extends TestCase
{
    public function testEmptyConfiguration(): void{
        $kernel = new DonationBundleTestingKernel();
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }

    public function testMissingPaymentConfiguration(): void{
        $kernel = new DonationBundleTestingKernel(['campaign_public_id' => 123]);
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }

    public function testMissingCampaignPublicIdConfiguration(): void{
        $kernel = new DonationBundleTestingKernel(['payments' => []]);
        $this->expectException(InvalidConfigurationException::class);
        $kernel->boot();
    }
}

class DonationBundleTestingKernel extends Kernel
{
    public function __construct(private array $donationConfig = [])
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable {
        return [
            new DonationBundle()
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void {
        $loader->load(function(ContainerBuilder $container){
            $container->loadFromExtension('donation', $this->donationConfig);
        });
    }
    
    public function getCacheDir(): string
    {
        // Ensure each kernel instance generates its own cache allowing different test cases do not reuse the cache
        return parent::getCacheDir().'/'.spl_object_hash($this);
    }
}