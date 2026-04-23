<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUseRejected;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class PaymentMethodUseRejectedHandler implements EventHandlerInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(PaymentMethodUseRejected $event): void
    {
        $this->commandBus->dispatch(new MarkPaymentAsFailed($event->paymentMethodAction->paymentId, null));
    }
}
