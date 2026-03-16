<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentAppliedToId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

class InitiatePaymentIntegrationCommand implements IntegrationCommandInterface
{
    /**
     * @param bool $usePaymentMethodId If true, paymentMethodId is for use, if false, it's for request.
     */
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly PaymentAppliedToId $appliedTo,
        public readonly ?Email $email = null,
        public readonly ?PaymentMethodId $paymentMethodId = null,
        public readonly bool $usePaymentMethodId = false,
    ) {
    }
}
