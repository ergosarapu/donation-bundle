<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('donation_bundle.test.payum.action.get_standing_amount', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestStandingAmountAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);
    
    $services->set('donation_bundle.test.payum.action.convert_payment', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestConvertPaymentAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);
};
