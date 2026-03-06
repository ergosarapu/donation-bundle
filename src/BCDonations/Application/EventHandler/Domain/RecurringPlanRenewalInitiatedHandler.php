<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;
use LogicException;

class RecurringPlanRenewalInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringPlanRenewalInitiated $event): void
    {
        if ($event->donorDetails === null) {
            throw new LogicException('Missing donor details. Personal info may have been deleted.');
        }

        $donationRequest = new DonationRequest(
            $event->renewalDonationId,
            $event->campaignId,
            $event->amount,
            $event->gateway,
            $event->donorDetails,
            $event->description,
        );

        $this->commandBus->dispatch(new InitiateDonation(
            $donationRequest,
            $event->recurringPlanId,
            $event->recurringPlanAction,
        ));
    }
}
