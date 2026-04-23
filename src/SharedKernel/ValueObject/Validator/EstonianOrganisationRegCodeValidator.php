<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\IdentifierType;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;

final class EstonianOrganisationRegCodeValidator implements LegalIdentifierValidatorInterface
{
    public function supports(LegalIdentifier $legalIdentifier): bool
    {
        return $legalIdentifier->country?->value === 'EE'
            && $legalIdentifier->identifierType === IdentifierType::OrganisationRegNumber;
    }

    public function isSatisfiedBy(string $value): bool
    {
        if (!$this->hasValidFormat($value)) {
            return false;
        }

        return $this->hasValidControlNumber($value);
    }

    private function hasValidFormat(string $value): bool
    {
        if (strlen($value) !== 8) {
            return false;
        }

        $hasValidPrefix = array_reduce(
            ['1', '7', '8', '9'],
            static fn (bool $hasValidPrefix, string $prefix): bool => $hasValidPrefix || $prefix === $value[0],
            false,
        );

        if (!$hasValidPrefix) {
            return false;
        }

        return strspn($value, '0123456789') === 8;
    }

    private function hasValidControlNumber(string $value): bool
    {
        return (int) $value[7] === $this->controlNumber($value);
    }

    private function controlNumber(string $value): int
    {
        $modulo = $this->controlNumberModulo($value, 1);

        if ($modulo < 10) {
            return $modulo;
        }

        $modulo = $this->controlNumberModulo($value, 3);

        if ($modulo < 10) {
            return $modulo;
        }
        return 0;
    }

    private function controlNumberModulo(string $value, int $multiplierOffset): int
    {
        $total = array_reduce(
            range(0, 6),
            static function (int $total, int $i) use ($value, $multiplierOffset): int {
                $multiplier = $i + $multiplierOffset;

                return $total + (int) $value[$i] * ($multiplier > 9 ? $multiplier - 9 : $multiplier);
            },
            0,
        );

        return $total % 11;
    }
}
