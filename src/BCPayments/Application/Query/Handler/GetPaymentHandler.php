<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPaymentHandler implements QueryHandlerInterface
{
    public function __construct(private readonly PaymentProjectionRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(GetPayment $query): mixed
    {
        return $this->paymentRepository->findOne($query->paymentId);
    }
}
