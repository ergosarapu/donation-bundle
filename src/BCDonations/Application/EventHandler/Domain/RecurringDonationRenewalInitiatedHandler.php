<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class RecurringDonationRenewalInitiatedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(RecurringDonationRenewalInitiated $event): void
    {
        $this->commandBus->dispatch(new InitiateDonation(
            DonationId::generate(),
            $event->amount,
            $event->campaignId,
            $event->gateway,
            false, // not initial recurring
            $event->recurringDonationId,
            null, // donor name
            $event->donorEmail,
            null, // donor national ID code
            $event->activationDonationId,
        ));
    }
}
