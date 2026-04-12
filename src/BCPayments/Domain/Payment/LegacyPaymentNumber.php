<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

/**
 * @deprecated This class is deprecated and will be removed in a future version.
 */
#[ObjectNormalizer]
final class LegacyPaymentNumber
{
    public readonly string $value;

    public function __construct(string $value)
    {
        trigger_error(sprintf('Class %s is deprecated.', self::class), E_USER_DEPRECATED);
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Legacy payment number cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Legacy payment number must contain ASCII characters only.');
        }
        if (strlen($value) > 128) {
            throw new \InvalidArgumentException(sprintf('Legacy payment number cannot exceed 128 characters, got %d.', strlen($value)));
        }
        $this->value = $value;
    }
}
