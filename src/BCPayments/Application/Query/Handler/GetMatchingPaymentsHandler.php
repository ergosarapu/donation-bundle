<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentsMatcherInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetMatchingPayments;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;

class GetMatchingPaymentsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PaymentsMatcherInterface $paymentsMatcher,
    ) {
    }

    public function __invoke(GetMatchingPayments $query): mixed
    {
        return $this->paymentsMatcher->match($query->paymentId);
    }
}
