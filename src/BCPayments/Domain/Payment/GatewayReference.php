<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class GatewayReference
{
    public readonly string $value;

    public function __construct(string $value)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Gateway reference cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Gateway reference must contain ASCII characters only.');
        }
        if (strlen($value) > 128) {
            throw new \InvalidArgumentException(sprintf('Gateway reference cannot exceed 128 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
