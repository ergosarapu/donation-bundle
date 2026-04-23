<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Bic
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $trimmed */
        $trimmed = mb_trim($value);
        $normalized = mb_strtoupper($trimmed);
        if ($normalized === '') {
            throw new \InvalidArgumentException('BIC cannot be empty.');
        }
        if (strlen($normalized) > 11) {
            throw new \InvalidArgumentException(sprintf('BIC cannot exceed 11 characters, got %d.', strlen($normalized)));
        }
        $this->value = $normalized;
    }
}
