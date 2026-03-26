<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetInitiatedPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetInitiatedPaymentHandler implements QueryHandlerInterface
{
    public function __construct(private readonly PaymentProjectionRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(GetInitiatedPayment $query): mixed
    {

        return $this->paymentRepository->findOne($query->paymentId, PaymentStatus::Initiated);
    }
}
