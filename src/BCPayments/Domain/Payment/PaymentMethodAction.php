<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class PaymentMethodAction
{
    private function __construct(
        public readonly PaymentMethodId $paymentMethodId,
        public readonly PaymentId $paymentId,
        public readonly PaymentMethodActionIntent $intent,
    ) {
    }

    public static function forRequest(
        PaymentMethodId $paymentMethodId,
        PaymentId $paymentId,
    ): self {
        return new self(
            $paymentMethodId,
            $paymentId,
            PaymentMethodActionIntent::Request,
        );
    }

    public static function forUse(
        PaymentMethodId $paymentMethodId,
        PaymentId $paymentId,
    ): self {
        return new self(
            $paymentMethodId,
            $paymentId,
            PaymentMethodActionIntent::Use,
        );
    }
}
