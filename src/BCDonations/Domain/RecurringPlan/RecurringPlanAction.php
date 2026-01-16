<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;

class RecurringPlanAction
{
    private function __construct(
        public readonly RecurringPlanId $recurringPlanId,
        public readonly PaymentMethodlId $paymentMethodId,
        public readonly RecurringPlanActionIntent $intent,
    ) {
    }

    public static function forInit(
    ): self {
        return new self(
            RecurringPlanId::generate(),
            PaymentMethodlId::generate(),
            RecurringPlanActionIntent::Init,
        );
    }

    public static function forRenew(
        RecurringPlanId $recurringPlanId,
        PaymentMethodlId $paymentMethodId,
    ): self {
        return new self(
            $recurringPlanId,
            $paymentMethodId,
            RecurringPlanActionIntent::Renew,
        );
    }
}
