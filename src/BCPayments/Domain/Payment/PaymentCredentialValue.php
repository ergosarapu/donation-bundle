<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class PaymentCredentialValue
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}
