<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class AcceptanceTestingKernel extends DonationBundleTestingKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();
        yield new FriendsOfBehatSymfonyExtensionBundle();
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        parent::configureContainer($container, $loader, $builder);

        $builder->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    // Intercept delayed messages to avoid triggering immediate handling
                    'delayed' => 'test://?intercept=true&catch_exceptions=false&support_delay_stamp=true',
                ],
            ]
        ]);

        $builder->loadFromExtension('patchlevel_event_sourcing', [
            'subscription' => [
                'store' => [
                    'type' => 'in_memory',
                ]
            ],
            'store' => [
                'type' => 'in_memory',
            ]
        ]);
    }
}
