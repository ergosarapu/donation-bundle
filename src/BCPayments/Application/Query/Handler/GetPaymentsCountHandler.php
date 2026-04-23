<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentsCount;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPaymentsCountHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PaymentProjectionRepositoryInterface $paymentRepository,
    ) {
    }

    public function __invoke(GetPaymentsCount $query): int
    {
        return $this->paymentRepository->countBy($query->importStatus);
    }
}
