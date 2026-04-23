<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
class PaymentCredentialValue
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Payment credential value cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Payment credential value must contain ASCII characters only.');
        }
        if (strlen($value) > 512) {
            throw new \InvalidArgumentException(sprintf('Payment credential value cannot exceed 512 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
