<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentDidNotSucceedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentDidNotSucceedIntegrationEvent $event): void
    {
        if ($event->appliedTo === null) {
            // This event is not for us
            return;
        }

        $this->commandBus->dispatch(new MarkDonationAsFailed(DonationId::fromString($event->appliedTo->toString())));
    }
}
