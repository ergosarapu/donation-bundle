<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentByTrackingId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class GetPaymentByTrackingIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TrackingStatusProjectionRepositoryInterface $trackingStatusRepository,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetPaymentByTrackingId $query): mixed
    {
        $status = $this->trackingStatusRepository->find($query->trackingId);
        $paymentId = $status?->getPaymentId();
        if ($paymentId === null) {
            return null;
        }
        return $this->queryBus->ask(new GetPayment(PaymentId::fromString($paymentId)));
    }
}
