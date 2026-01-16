<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

interface PaymentProjectionRepositoryInterface
{
    public function findOne(?PaymentId $id = null, ?PaymentStatus $status = null): ?Payment;
}
