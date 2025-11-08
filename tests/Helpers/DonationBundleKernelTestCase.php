<?php

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class DonationBundleKernelTestCase extends KernelTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        $bundleConfig = $options['bundle_config'] ?? [];
        
        return new DonationBundleTestingKernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? true,
            $bundleConfig
        );
    }

    protected static function getKernelClass(): string
    {
        return DonationBundleTestingKernel::class;
    }
}
