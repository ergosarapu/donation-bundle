<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\FailRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetRecurringPlanByPaymentMethod;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentMethodUnusableHandler implements EventHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(PaymentMethodUnusableIntegrationEvent $event): void
    {
        /** @var ?RecurringPlan $plan */
        $plan = $this->queryBus->ask(new GetRecurringPlanByPaymentMethod(
            $event->paymentMethodId,
        ));

        if ($plan === null) {
            throw new \LogicException('No recurring plan found for unusable payment method: ' . $event->paymentMethodId->toString());
        }

        $this->commandBus->dispatch(
            new FailRecurringPlan(
                RecurringPlanId::fromString($plan->getRecurringPlanId()),
            )
        );
    }
}
