<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class RecurringPlanAction
{
    private function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly PaymentMethodId $paymentMethodId,
        public readonly RecurringPlanActionIntent $intent,
    ) {
    }

    public static function forInit(
        RecurringPlanId $recurringPlanId
    ): self {
        return new self(
            $recurringPlanId,
            PaymentMethodId::generate(),
            RecurringPlanActionIntent::Init,
        );
    }

    public static function forRenew(
        RecurringPlanId $recurringPlanId,
        PaymentMethodId $paymentMethodId,
    ): self {
        return new self(
            $recurringPlanId,
            $paymentMethodId,
            RecurringPlanActionIntent::Renew,
        );
    }
}
