<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPendingPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPendingPaymentHandler implements QueryHandlerInterface
{
    public function __construct(private readonly PaymentProjectionRepositoryInterface $paymentRepository)
    {
    }

    public function __invoke(GetPendingPayment $query): mixed
    {

        return $this->paymentRepository->findOne($query->paymentId, PaymentStatus::Pending);
    }
}
