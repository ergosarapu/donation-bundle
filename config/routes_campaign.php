<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('donation_landing', '/{campaignSlug}/{step}')
        ->controller('donation_bundle.controller.index_controller')
        ->defaults([
            'campaignSlug' => 'default',
            'template' => 'landing',
            'step' => 1,
        ])
        ->requirements([
            'step' => '\d+',
        ]);

    $routes->add('donation_embed', '/{campaignSlug}/{template}/{step}')
        ->controller('donation_bundle.controller.index_controller')
        ->defaults([
            'campaignSlug' => 'default',
            'step' => 1,
        ])
        ->requirements([
            'template' => 'embed', // Only allow 'embed' template
            'step' => '\d+',
        ]);
};
