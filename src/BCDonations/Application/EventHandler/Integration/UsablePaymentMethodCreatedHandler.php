<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class UsablePaymentMethodCreatedHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RecurringPlanRepositoryInterface $recurringPlanRepository,
    ) {
    }

    public function __invoke(UsablePaymentMethodCreatedIntegrationEvent $event): void
    {
        $recurringPlanId = RecurringPlanId::fromString($event->createdFor->toString());
        if (!$this->recurringPlanRepository->has($recurringPlanId)) {
            return;
        }

        $this->commandBus->dispatch(
            new ActivateRecurringPlan(
                $recurringPlanId,
                $event->paymentMethodId->toString(),
            )
        );
    }
}
