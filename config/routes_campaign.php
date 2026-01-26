<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('donation_landing', '/{campaign}/{step}')
        ->controller('donation_bundle.controller.index_controller')
        ->defaults([
            'campaign' => 'default',
            'template' => 'landing',
            'step' => 1,
        ])
        ->requirements([
            'step' => '\d+',
        ]);
    
    $routes->add('donation_embed', '/{campaign}/{template}/{step}')
        ->controller('donation_bundle.controller.index_controller')
        ->defaults([
            'campaign' => 'default',
            'step' => 1,
        ])
        ->requirements([
            'template' => 'embed', // Only allow 'embed' template
            'step' => '\d+',
        ]);
};
