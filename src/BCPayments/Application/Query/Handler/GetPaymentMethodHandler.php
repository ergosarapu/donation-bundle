<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentMethodProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetPaymentMethodHandler implements QueryHandlerInterface
{
    public function __construct(private readonly PaymentMethodProjectionRepositoryInterface $paymentMethodRepository)
    {
    }

    public function __invoke(GetPaymentMethod $query): mixed
    {
        return $this->paymentMethodRepository->findOne($query->paymentMethodId);
    }
}
