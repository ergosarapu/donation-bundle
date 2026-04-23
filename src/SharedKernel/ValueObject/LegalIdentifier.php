<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator\EstonianNationalIdCodeValidator;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator\EstonianOrganisationRegCodeValidator;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Validator\LegalIdentifierValidatorInterface;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class LegalIdentifier
{
    public readonly string $value;
    public readonly IdentifierType $identifierType;
    public readonly ?Country $country;

    public function __construct(
        string $value,
        IdentifierType $identifierType,
        ?Country $country = null,
    ) {
        /** @var string $value */
        $value = mb_trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Legal identifier cannot be empty.');
        }
        if (!mb_check_encoding($value, 'ASCII')) {
            throw new \InvalidArgumentException('Legal identifier must contain ASCII characters only.');
        }

        $this->value = $value;
        $this->identifierType = $identifierType;
        $this->country = $country;

        $this->validate();
    }

    public static function nationalIdNumber(string $value, ?Country $country = null): self
    {
        return new self($value, IdentifierType::NationalIdNumber, $country);
    }

    public static function organisationRegNumber(string $value, ?Country $country = null): self
    {
        return new self($value, IdentifierType::OrganisationRegNumber, $country);
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }
        if ($this->value !== $other->value) {
            return false;
        }
        if ($this->identifierType !== $other->identifierType) {
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

        return $this->value === $other->value
            && $this->identifierType === $other->identifierType;
    }

    public function __toString(): string
    {
        if ($this->country === null) {
            return $this->value;
        }

        return sprintf('%s %s', $this->country->value, $this->value);
    }

    private function validate(): void
    {
        $instance = $this;
        $validators = array_values(array_filter(
            self::validators(),
            static fn (LegalIdentifierValidatorInterface $validator): bool => $validator->supports($instance),
        ));

        if ($validators === []) {
            return;
        }

        /** @var LegalIdentifierValidatorInterface $validator */
        $validator = $validators[0];

        if ($validator->isSatisfiedBy($this->value)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            '"%s" is not a valid %s for country "%s".',
            $this->value,
            $this->identifierType->value,
            $this->country?->value,
        ));
    }

    /**
     * @return list<LegalIdentifierValidatorInterface>
     */
    private static function validators(): array
    {
        return [
            new EstonianNationalIdCodeValidator(),
            new EstonianOrganisationRegCodeValidator(),
        ];
    }
}
