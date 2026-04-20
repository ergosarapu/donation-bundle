<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<Payment, PaymentId>
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
}
