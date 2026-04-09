<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use ErgoSarapu\DonationBundle\Tests\Helpers\AcceptanceTestingKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class MessageMetadataTestingKernel extends AcceptanceTestingKernel
{
    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        parent::configureContainer($container, $loader, $builder);

        $builder->loadFromExtension('patchlevel_event_sourcing', [
            'aggregates' => [
                __DIR__ ,
            ],
            'events' => [
                __DIR__ ,
            ],
        ]);

        $builder->loadFromExtension('framework', [
            'messenger' => [
                'transports' => [
                    // Define separate transports for each message type to allow for more granular testing of metadata handling
                    'cmd1' => 'test://?intercept=true&catch_exceptions=false&support_delay_stamp=true',
                    'evt1' => 'test://?intercept=true&catch_exceptions=false&support_delay_stamp=true',
                    'evt2' => 'test://?intercept=true&catch_exceptions=false&support_delay_stamp=true',
                ],
                'routing' => [
                    FirstCommand::class => 'cmd1',
                    FirstEvent::class => 'evt1',
                    SecondEvent::class => 'evt2',
                ],
            ]
        ]);
    }
}
