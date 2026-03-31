<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command;

use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;

class InitiatePaymentIntegrationCommand implements IntegrationCommandInterface
{
    /**
     * @param ?ExternalEntityId $requestPaymentMethodFor Non-null means BCPayments generates a new PaymentMethodId
     *                                                   and uses this as `createFor` on the resulting payment method
     *                                                   (only valid when paymentMethodId is null).
     */
    public function __construct(
        public readonly Money $amount,
        public readonly Gateway $gateway,
        public readonly ShortDescription $description,
        public readonly ExternalEntityId $appliedTo,
        public readonly ?Email $email = null,
        public readonly ?ExternalEntityId $paymentMethodId = null,
        public readonly ?ExternalEntityId $requestPaymentMethodFor = null,
    ) {
    }
}
