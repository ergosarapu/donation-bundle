<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentMethodByTrackingId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\TrackingStatusProjectionRepositoryInterface;

class GetPaymentMethodByTrackingIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TrackingStatusProjectionRepositoryInterface $trackingStatusRepository,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(GetPaymentMethodByTrackingId $query): mixed
    {
        $status = $this->trackingStatusRepository->find($query->trackingId);
        $paymentMethodId = $status?->getPaymentMethodId();
        if ($paymentMethodId === null) {
            return null;
        }
        return $this->queryBus->ask(new GetPaymentMethod(PaymentMethodId::fromString($paymentMethodId)));
    }
}
