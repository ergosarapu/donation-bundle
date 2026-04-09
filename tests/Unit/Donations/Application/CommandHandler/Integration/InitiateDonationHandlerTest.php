<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration\InitiateDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Interval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InitiateDonationHandlerTest extends TestCase
{
    private InitiateDonationHandler $handler;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new InitiateDonationHandler($this->commandBus);
    }

    public function testDispatchesInitiateDonationCommandWhenNoRecurringInterval(): void
    {
        $integrationCommand = new InitiateDonationIntegrationCommand(
            campaignId: 'f47ac10b-58cc-4372-a567-0e02b2c3d480',
            amount: new Money(5000, new Currency('EUR')),
            gateway: new Gateway('test-gateway'),
            description: new ShortDescription('Test donation'),
            donorEmail: new Email('donor@example.com'),
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof InitiateDonation
                    && $command->recurringPlanAction === null
                    && $command->recurringPlanId === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        $result = ($this->handler)($integrationCommand);
        $this->assertInstanceOf(DonationId::class, $result);
    }

    public function testDispatchesInitiateRecurringPlanCommandWhenRecurringIntervalProvided(): void
    {
        $integrationCommand = new InitiateDonationIntegrationCommand(
            campaignId: 'f47ac10b-58cc-4372-a567-0e02b2c3d480',
            amount: new Money(5000, new Currency('EUR')),
            gateway: new Gateway('test-gateway'),
            description: new ShortDescription('Test donation'),
            donorEmail: new Email('donor@example.com'),
            recurringInterval: new Interval(Interval::Monthly),
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof InitiateRecurringPlan
                    && $command->interval->toString() === Interval::Monthly;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        $result = ($this->handler)($integrationCommand);
        $this->assertInstanceOf(DonationId::class, $result);
    }
}
