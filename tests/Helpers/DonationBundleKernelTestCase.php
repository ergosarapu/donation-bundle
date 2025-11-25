<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class DonationBundleKernelTestCase extends KernelTestCase
{
    /**
     * @param array{
     *     environment?: string,
     *     debug?: bool,
     *     bundle_config?: array<string, mixed>|string
     * } $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $environment = $options['environment'] ?? 'test';
        $debug = $options['debug'] ?? true;
        $bundleConfig = $options['bundle_config'] ?? null;

        return new DonationBundleTestingKernel(
            $environment,
            $debug,
            $bundleConfig
        );
    }

    protected static function getKernelClass(): string
    {
        return DonationBundleTestingKernel::class;
    }
}
