<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class PaymentMethodAction
{
    private function __construct(
        public readonly PaymentMethodId $paymentMethodId,
        public readonly PaymentId $paymentId,
        public readonly PaymentMethodActionIntent $intent,
        private readonly ?ExternalEntityId $createFor = null,
    ) {
    }

    public static function forRequest(
        PaymentMethodId $paymentMethodId,
        PaymentId $paymentId,
        ExternalEntityId $createFor,
    ): self {
        return new self(
            $paymentMethodId,
            $paymentId,
            PaymentMethodActionIntent::Request,
            $createFor,
        );
    }

    public function getCreateFor(): ExternalEntityId
    {
        if ($this->createFor === null) {
            throw new \LogicException('CreateFor is only available for request intent.');
        }
        return $this->createFor;
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
