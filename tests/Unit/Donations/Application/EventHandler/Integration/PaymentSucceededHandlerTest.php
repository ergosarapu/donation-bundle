<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\AcceptDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\PaymentSucceededHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject\EntityId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentSucceededHandlerTest extends TestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private PaymentSucceededHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new PaymentSucceededHandler($this->commandBus);
    }

    public function testDispatchesAcceptDonationWithPaymentId(): void
    {
        $paymentId = new EntityId('018f4d99-6e26-7d76-8f3a-261fbf547501');
        $donationId = DonationId::generate();
        $amount = new Money(5000, new Currency('EUR'));
        $event = new PaymentSucceededIntegrationEvent($paymentId, $amount, new EntityId($donationId->toString()));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (object $command) use ($donationId, $amount, $paymentId): bool {
                return $command instanceof AcceptDonation
                    && $command->donationId->toString() === $donationId->toString()
                    && $command->amount->equals($amount)
                    && $command->paymentId === $paymentId->toString();
            }))
            ->willReturn(new CommandResult(null, 'tracking-id'));

        ($this->handler)($event);
    }

    public function testIgnoresEventWhenDonationIdMissing(): void
    {
        $event = new PaymentSucceededIntegrationEvent(
            new EntityId('018f4d99-6e26-7d76-8f3a-261fbf547502'),
            new Money(5000, new Currency('EUR')),
            null,
        );

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($event);
    }
}
