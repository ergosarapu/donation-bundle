<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\ValueObject;

/** Opaque reference to an entity passed across integration boundaries. */
final class EntityId
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private readonly string $id;

    public function __construct(string $id)
    {
        if (!preg_match(self::UUID_PATTERN, $id)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid UUID.', $id));
        }
        $this->id = $id;
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
