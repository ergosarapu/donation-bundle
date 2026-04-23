<?php

declare(strict_types=1);
use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat\DonationsContext;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat\IdentitiesContext;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat\PaymentsContext;
use ErgoSarapu\DonationBundle\Tests\Helpers\AcceptanceTestingKernel;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withExtension(new Extension(SymfonyExtension::class, [
                'kernel' => [
                    'class' => AcceptanceTestingKernel::class,
                ],
            ]))
            ->withSuite(
                (new Suite(
                    'payments',
                    [
                        'paths' => ['%paths.base%/tests/Acceptance/Payments']
                    ]
                ))->withContexts(PaymentsContext::class)
            )
            ->withSuite(
                (new Suite(
                    'donations',
                    [
                        'paths' => ['%paths.base%/tests/Acceptance/Donations']
                    ]
                ))->withContexts(DonationsContext::class)
            )
            ->withSuite(
                (new Suite(
                    'identities',
                    [
                        'paths' => ['%paths.base%/tests/Acceptance/Identities']
                    ]
                ))->withContexts(IdentitiesContext::class)
            )
    )
;
