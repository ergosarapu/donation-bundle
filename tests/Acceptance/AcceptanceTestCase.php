<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;
use ErgoSarapu\DonationBundle\Tests\Helpers\AcceptanceTestingKernel;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

abstract class AcceptanceTestCase extends KernelTestCase
{
    use InteractsWithMessenger;

    protected CommandBusInterface $commandBus;

    protected EventBusInterface $eventBus;

    protected SubscriptionEngine $subscriptionEngine;

    public function setUp(): void
    {
        parent::setUp();
        $this->commandBus = static::getContainer()->get(CommandBusInterface::class);
        $this->eventBus = static::getContainer()->get(EventBusInterface::class);
        $this->subscriptionEngine = static::getContainer()->get(SubscriptionEngine::class);
        $this->subscriptionEngine->boot();
    }

    protected static function getKernelClass(): string
    {
        return AcceptanceTestingKernel::class;
    }
}
