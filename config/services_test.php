<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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

    // Test Command Bus
    $services->set('donation_bundle.test.infrastructure.bus.command_bus', ErgoSarapu\DonationBundle\Tests\Helpers\TestCommandBus::class)
        ->args([service('donation_bundle.infrastructure.bus.command_bus')]);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface::class, 'donation_bundle.test.infrastructure.bus.command_bus')
        ->public();
    $services->alias(ErgoSarapu\DonationBundle\Tests\Helpers\TestCommandBus::class, 'donation_bundle.test.infrastructure.bus.command_bus')
        ->public();

    // Test Event Bus
    $services->set('donation_bundle.test.infrastructure.bus.event_bus', ErgoSarapu\DonationBundle\Tests\Helpers\TestEventBus::class)
        ->args([service('donation_bundle.infrastructure.bus.event_bus')]);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface::class, 'donation_bundle.test.infrastructure.bus.event_bus')
        ->public();
    $services->alias(ErgoSarapu\DonationBundle\Tests\Helpers\TestEventBus::class, 'donation_bundle.test.infrastructure.bus.event_bus')
        ->public();
};
