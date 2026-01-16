<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers\DependencyInjection\Compiler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\FakeGateway;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TestingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Replace the payment gateway service with FakeGateway for testing
        $container->register('donation_bundle.application.payment.port.payment_gateway', FakeGateway::class)
            ->setPublic(false);
        $container->setAlias(FakeGateway::class, 'donation_bundle.application.payment.port.payment_gateway');

        // Setup frozen clock for deterministic testing
        $dateTime = new Definition(DateTimeImmutable::class, ['2025-12-01T00:00:00+00:00']);
        $container->setDefinition('donation_bundle.test.clock.frozen', new Definition(FrozenClock::class, [$dateTime]));
        $container->setAlias(ClockInterface::class, 'donation_bundle.test.clock.frozen')
            ->setPublic(true);
    }
}
