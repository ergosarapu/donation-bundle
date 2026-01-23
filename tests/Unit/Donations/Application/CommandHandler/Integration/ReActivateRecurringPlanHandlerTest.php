<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ReActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration\ReActivateRecurringPlanHandler;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\ReActivateRecurringPlanIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReActivateRecurringPlanHandlerTest extends TestCase
{
    private ReActivateRecurringPlanHandler $handler;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new ReActivateRecurringPlanHandler($this->commandBus);
    }

    public function testDispatchesReActivateRecurringPlanCommand(): void
    {
        $recurringPlanId = RecurringPlanId::generate();

        $integrationCommand = new ReActivateRecurringPlanIntegrationCommand($recurringPlanId);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($recurringPlanId) {
                return $command instanceof ReActivateRecurringPlan
                    && $command->recurringPlanId === $recurringPlanId;
            }));

        ($this->handler)($integrationCommand);
    }
}
