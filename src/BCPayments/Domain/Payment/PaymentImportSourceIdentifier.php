<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class PaymentImportSourceIdentifier
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Payment import source identifier cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Payment import source identifier must contain ASCII characters only.');
        }
        if (strlen($value) > 128) {
            throw new \InvalidArgumentException(sprintf('Payment import source identifier cannot exceed 128 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
