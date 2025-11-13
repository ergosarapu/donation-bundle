<?php

declare(strict_types=1);

use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleTestingKernel;

require_once __DIR__ . '/vendor/autoload.php';
// Build container cache for phpstan analysis
$kernel = new DonationBundleTestingKernel('test', true, cachePath: 'phpstan');
$kernel->boot();
