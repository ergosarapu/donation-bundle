<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentByInitiatedCorrelationId;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPaymentByInitiatedCorrelationIdHandler implements QueryHandlerInterface
{
    public function __construct(private readonly PaymentProjectionRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(GetPaymentByInitiatedCorrelationId $query): mixed
    {
        return $this->paymentRepository->findOneByCorrelationId($query->correlationId);
    }
}
