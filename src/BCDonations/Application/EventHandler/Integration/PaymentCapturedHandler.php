<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkDonationAsAccepted;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentCapturedIntegrationEvent;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentCapturedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentCapturedIntegrationEvent $event): void
    {
        if ($event->donationId === null) {
            // This event is not for us
            return;
        }

        $this->commandBus->dispatch(new MarkDonationAsAccepted($event->donationId, $event->capturedAmount));
    }
}
