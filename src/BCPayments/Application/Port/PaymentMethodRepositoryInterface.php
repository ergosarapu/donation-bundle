<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\RepositoryInterface;

/**
 * @extends RepositoryInterface<PaymentMethod, PaymentMethodId>
 */
interface PaymentMethodRepositoryInterface extends RepositoryInterface
{
}
