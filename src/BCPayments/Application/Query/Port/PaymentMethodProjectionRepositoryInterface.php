<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;

interface PaymentMethodProjectionRepositoryInterface
{
    public function findOne(PaymentMethodId $id): ?PaymentMethod;
}
