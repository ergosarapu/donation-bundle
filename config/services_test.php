<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    // Register Behat contexts for autowiring
    $services->set(ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat\PaymentsContext::class)
        ->class(ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat\PaymentsContext::class)
        ->public(true)->autowire();
    $services->set(ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat\DonationsContext::class)
        ->class(ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat\DonationsContext::class)
        ->public(true)->autowire();


    $services->set('donation_bundle.test.payum.action.get_standing_amount', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestStandingAmountAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);

    $services->set('donation_bundle.test.payum.action.convert_payment', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestConvertPaymentAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);

};
