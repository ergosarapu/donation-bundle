<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ExternalEntityId;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class RecurringPlanAction
{
    private function __construct(
        public readonly ?ExternalEntityId $paymentMethodId,
        public readonly RecurringPlanActionIntent $intent,
    ) {
    }

    public static function forInit(
    ): self {
        return new self(
            null,
            RecurringPlanActionIntent::Init,
        );
    }

    public static function forRenew(
        ExternalEntityId $paymentMethodId,
    ): self {
        return new self(
            $paymentMethodId,
            RecurringPlanActionIntent::Renew,
        );
    }
}
