<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class AcceptanceTestingKernel extends DonationBundleTestingKernel
{
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        parent::configureContainer($container, $loader, $builder);

        $builder->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    'event' => 'test://?intercept=false&catch_exceptions=false',
                    'command' => 'test://?intercept=false&catch_exceptions=false',
                    // Intercept integration messages and avoid triggering handlers outside of bounded context
                    'integration_event' => 'test://?intercept=true&catch_exceptions=false',
                    'integration_command' => 'test://?intercept=true&catch_exceptions=false',
                ]
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
