<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\IdentifierType;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;

final class EstonianNationalIdCodeValidator implements LegalIdentifierValidatorInterface
{
    public function supports(LegalIdentifier $legalIdentifier): bool
    {
        return $legalIdentifier->country?->value === 'EE'
            && $legalIdentifier->identifierType === IdentifierType::NationalIdNumber;
    }

    public function isSatisfiedBy(string $value): bool
    {
        if (!$this->hasValidFormat($value)) {
            return false;
        }

        return $this->hasValidBirthDate($value)
            && $this->hasValidControlNumber($value);
    }

    private function hasValidFormat(string $value): bool
    {
        if (strlen($value) !== 11) {
            return false;
        }

        $hasValidPrefix = array_reduce(
            ['1', '2', '3', '4', '5', '6', '7', '8'],
            static fn (bool $hasValidPrefix, string $prefix): bool => $hasValidPrefix || $prefix === $value[0],
            false,
        );

        if (!$hasValidPrefix) {
            return false;
        }

        return strspn($value, '0123456789') === 11;
    }

    private function hasValidBirthDate(string $value): bool
    {
        $year = $this->birthCentury($value) + (int) mb_substr($value, 1, 2);
        $month = (int) mb_substr($value, 3, 2);
        $day = (int) mb_substr($value, 5, 2);

        return checkdate($month, $day, $year);
    }

    private function birthCentury(string $value): int
    {
        return 1700 + (int) ceil(((int) $value[0]) / 2) * 100;
    }

    private function hasValidControlNumber(string $value): bool
    {
        return (int) $value[10] === $this->controlNumber($value);
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
            range(0, 9),
            static function (int $total, int $i) use ($value, $multiplierOffset): int {
                $multiplier = $i + $multiplierOffset;

                return $total + (int) $value[$i] * ($multiplier > 9 ? $multiplier - 9 : $multiplier);
            },
            0,
        );

        return $total % 11;
    }
}
