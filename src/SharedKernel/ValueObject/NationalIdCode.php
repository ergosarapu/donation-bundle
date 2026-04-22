<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator\CountryBasedValidatorInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator\EstonianNationalIdCodeValidator;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class NationalIdCode
{
    public readonly string $value;
    public readonly ?Country $country;

    public function __construct(string $value, ?Country $country = null)
    {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('National ID code cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('National ID code must contain ASCII characters only.');
        }

        if ($country !== null) {
            self::validateForCountry($value, $country);
        }

        $this->value = $value;
        $this->country = $country;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }
        if ($this->value !== $other->value) {
            return false;
        }
        if ($this->country !== null) {
            return $this->country->equals($other->country);
        }
        if ($other->country !== null) {
            return false;
        }
        return true;
    }

    public function hasSameValueAs(?self $other): bool
    {
        if ($other === null) {
            return false;
        }
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        if ($this->country === null) {
            return $this->value;
        }

        return sprintf('%s %s', $this->country->value, $this->value);
    }

    private static function validateForCountry(string $value, Country $country): void
    {
        $validators = array_values(array_filter(
            self::validators(),
            static fn (CountryBasedValidatorInterface $validator): bool => $validator->supports($country),
        ));

        if ($validators === []) {
            return;
        }

        /** @var CountryBasedValidatorInterface $validator */
        $validator = $validators[0];

        if ($validator->isSatisfiedBy($value)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('"%s" is not a valid national ID code for country "%s".', $value, $country->value));
    }

    /**
     * @return list<CountryBasedValidatorInterface>
     */
    private static function validators(): array
    {
        return [
            new EstonianNationalIdCodeValidator(),
        ];
    }
}
