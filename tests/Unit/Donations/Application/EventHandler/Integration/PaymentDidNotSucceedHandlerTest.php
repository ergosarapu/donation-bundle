<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\FailDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\PaymentDidNotSucceedHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentDidNotSucceedHandlerTest extends TestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private PaymentDidNotSucceedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new PaymentDidNotSucceedHandler($this->commandBus);
    }

    public function testDispatchesFailDonationWithPaymentId(): void
    {
        $paymentId = new EntityId('018f4d99-6e26-7d76-8f3a-261fbf547503');
        $donationId = DonationId::generate();
        $event = new PaymentDidNotSucceedIntegrationEvent($paymentId, new EntityId($donationId->toString()));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (object $command) use ($donationId, $paymentId): bool {
                return $command instanceof FailDonation
                    && $command->donationId->toString() === $donationId->toString()
                    && $command->paymentId === $paymentId->toString();
            }))
            ->willReturn(new CommandResult(null, 'tracking-id'));

        ($this->handler)($event);
    }

    public function testIgnoresEventWhenAppliedToMissing(): void
    {
        $event = new PaymentDidNotSucceedIntegrationEvent(
            new EntityId('018f4d99-6e26-7d76-8f3a-261fbf547504'),
            null,
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($event);
    }
}
