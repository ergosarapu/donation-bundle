<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;

final class EstonianNationalIdCodeValidator implements CountryBasedValidatorInterface
{
    private const PERSONAL_CODE_REGEX = '/^[1-8][0-9]{10}$/';

    public function supports(Country $country): bool
    {
        return $country->value === 'EE';
    }

    public function isSatisfiedBy(string $value): bool
    {
        if (!preg_match(self::PERSONAL_CODE_REGEX, $value)) {
            return false;
        }

        return $this->hasValidBirthDate($value)
            && $this->hasValidControlNumber($value);
    }

    private function hasValidBirthDate(string $value): bool
    {
        $year = $this->birthCentury($value) + (int) substr($value, 1, 2);
        $month = (int) substr($value, 3, 2);
        $day = (int) substr($value, 5, 2);

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
