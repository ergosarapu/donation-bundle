<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Listener;

use Doctrine\ORM\Event\PostLoadEventArgs;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMatch;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetMatchingPayments;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;

class PendingPaymentImportListener
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function postLoad(Payment $payment, PostLoadEventArgs $event): void
    {
        if ($payment->getImportStatus() !== PaymentImportStatus::Review) {
            return;
        }

        $paymentId = PaymentId::fromString($payment->getPaymentId());

        /** @var array<PaymentMatch> $matchingPayments */
        $matchingPayments = $this->queryBus->ask(new GetMatchingPayments($paymentId));

        $payment->setMatchingPayments($matchingPayments);
    }
}
