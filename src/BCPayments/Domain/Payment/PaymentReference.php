<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class PaymentReference
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Payment reference cannot be empty.');
        }
        if (mb_strlen($value) > 35) {
            throw new \InvalidArgumentException(sprintf('Payment reference cannot exceed 35 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
