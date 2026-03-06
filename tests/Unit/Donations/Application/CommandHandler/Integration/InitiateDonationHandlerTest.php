<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration\InitiateDonationHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
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
        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            new Money(5000, new Currency('EUR')),
            new Gateway('test-gateway'),
            new DonorDetails(new Email('donor@example.com')),
            new ShortDescription('Test donation')
        );

        $integrationCommand = new InitiateDonationIntegrationCommand(
            $donationRequest,
            null
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($donationRequest) {
                return $command instanceof InitiateDonation
                    && $command->donationRequest === $donationRequest;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesInitiateRecurringPlanCommandWhenRecurringIntervalProvided(): void
    {
        $donationRequest = new DonationRequest(
            DonationId::generate(),
            CampaignId::generate(),
            new Money(5000, new Currency('EUR')),
            new Gateway('test-gateway'),
            new DonorDetails(new Email('donor@example.com')),
            new ShortDescription('Test donation')
        );

        $recurringInterval = new RecurringInterval(RecurringInterval::Monthly);

        $integrationCommand = new InitiateDonationIntegrationCommand(
            $donationRequest,
            $recurringInterval
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($recurringInterval, $donationRequest) {
                return $command instanceof InitiateRecurringPlan
                    && $command->interval === $recurringInterval
                    && $command->donationRequest === $donationRequest;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }
}
