<?php

declare(strict_types=1);

use Patchlevel\EventSourcing\Clock\FrozenClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('donation_bundle.test.payum.action.get_standing_amount', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestStandingAmountAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);

    $services->set('donation_bundle.test.payum.action.convert_payment', ErgoSarapu\DonationBundle\Tests\Helpers\Payum\TestConvertPaymentAction::class)
        ->public(true)
        ->tag('payum.action', ['all' => true]);

    $services->set('donation_bundle.test.clock.frozen', FrozenClock::class)
        ->args([inline_service(DateTimeImmutable::class)->args(['2024-01-01T00:00:00+00:00'])]);
    $services->alias(Psr\Clock\ClockInterface::class, 'donation_bundle.test.clock.frozen')->public(true);
};
