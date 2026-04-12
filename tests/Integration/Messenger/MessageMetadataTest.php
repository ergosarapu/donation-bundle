<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MetadataContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\FirstCommand;
use ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\FirstEvent;
use ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\MessageMetadataTestingKernel;
use ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\SecondEvent;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class MessageMetadataTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private CommandBusInterface $commandBus;
    private SubscriptionEngine $subscriptionEngine;

    public function setUp(): void
    {
        parent::setUp();
        $this->commandBus = static::getContainer()->get(CommandBusInterface::class);
        $this->subscriptionEngine = static::getContainer()->get(SubscriptionEngine::class);
        $this->subscriptionEngine->setup();
        $this->subscriptionEngine->boot();
    }

    public function tearDown(): void
    {
        $this->subscriptionEngine->remove();
        parent::tearDown();
    }
    protected static function getKernelClass(): string
    {
        return MessageMetadataTestingKernel::class;
    }

    /**
     * @param class-string $messageClass
     * @return MessageMetadataStamp
     */
    private function assertMessageStamp(string $transport, string $messageClass): MessageMetadataStamp
    {
        $this->transport($transport)->queue()->assertCount(1);
        $this->transport($transport)->queue()->assertContains($messageClass, 1);
        $envelope = $this->transport($transport)->queue()->first();
        $envelope->assertHasStamp(MessageMetadataStamp::class);
        $stamps = $envelope->all(MessageMetadataStamp::class);
        self::assertCount(1, $stamps);
        return $stamps[0];
    }

    public function testMetadataPropagates(): void
    {
        $result = $this->commandBus->dispatch(new FirstCommand());

        // Assert command was queued with MessageMetadataStamp
        $cmd1Stamp = $this->assertMessageStamp('cmd1', FirstCommand::class);
        self::assertNull($cmd1Stamp->causationId);
        self::assertEquals($result->trackingId, $cmd1Stamp->trackingId);

        // Process command
        $this->transport('cmd1')->processOrFail(1);

        // Assert first event was queued with MessageMetadataStamp
        $evt1Stamp = $this->assertMessageStamp('evt1', FirstEvent::class);
        self::assertNotNull($evt1Stamp->causationId);
        self::assertEquals($cmd1Stamp->correlationId, $evt1Stamp->correlationId);
        self::assertEquals($cmd1Stamp->messageId, $evt1Stamp->causationId);
        self::assertEquals($result->trackingId, $evt1Stamp->trackingId);

        // Process event
        $this->transport('evt1')->processOrFail(1);

        // Assert second event was queued with MessageMetadataStamp
        $evt2Stamp = $this->assertMessageStamp('evt2', SecondEvent::class);
        self::assertNotNull($evt2Stamp->causationId);
        self::assertEquals($cmd1Stamp->correlationId, $evt2Stamp->correlationId);
        self::assertEquals($evt1Stamp->messageId, $evt2Stamp->causationId);
        self::assertEquals($result->trackingId, $evt2Stamp->trackingId);
    }

    public function testMetadataContextResetsAfterMessage(): void
    {
        $this->commandBus->dispatch(new FirstCommand());

        // Assert command was queued with MessageMetadataStamp
        $cmd1Stamp = $this->assertMessageStamp('cmd1', FirstCommand::class);
        self::assertNull($cmd1Stamp->causationId);

        // Process command
        $this->transport('cmd1')->processOrFail(1);

        // Assert context is reset after handling message
        $metadataContext = static::getContainer()->get(MetadataContext::class);
        self::assertNull($metadataContext->getCorrelationId());
        self::assertNull($metadataContext->getPreviousMessageId());
        self::assertNull($metadataContext->getTrackingId());
    }

}
