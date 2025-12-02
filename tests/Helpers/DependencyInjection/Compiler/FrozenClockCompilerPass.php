<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers\DependencyInjection\Compiler;

use DateTimeImmutable;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FrozenClockCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $dateTime = new Definition(DateTimeImmutable::class, ['2025-12-01T00:00:00+00:00']);
        $container->setDefinition('donation_bundle.test.clock.frozen', new Definition(FrozenClock::class, [$dateTime]));
        $container->setAlias(ClockInterface::class, 'donation_bundle.test.clock.frozen')
            ->setPublic(true);
    }
}
