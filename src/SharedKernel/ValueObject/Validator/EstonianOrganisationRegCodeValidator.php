<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;

final class EstonianOrganisationRegCodeValidator implements CountryBasedValidatorInterface
{
    private const REGISTRATION_CODE_REGEX = '/^[1789][0-9]{7}$/';

    public function supports(Country $country): bool
    {
        return $country->value === 'EE';
    }

    public function isSatisfiedBy(string $value): bool
    {
        return preg_match(self::REGISTRATION_CODE_REGEX, $value) === 1
            && $this->hasValidControlNumber($value);
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
