<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Money\Currencies\ISOCurrencies;
use Money\Currency as MoneyCurrency;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Currency
{
    private readonly string $code;

    public function __construct(string $code)
    {
        $trimmed = mb_trim($code);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Currency code cannot be empty.');
        }
        $moneyCurrency = new MoneyCurrency($trimmed);
        $contains = new ISOCurrencies()->contains($moneyCurrency);
        if (!$contains) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid ISO currency code.', $moneyCurrency->getCode()));
        }
        $this->code = $moneyCurrency->getCode();
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(Currency $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
