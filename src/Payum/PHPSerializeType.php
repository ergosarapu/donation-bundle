<?php

namespace ErgoSarapu\DonationBundle\Payum;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class PHPSerializeType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string {
        return null === $value ? null : serialize($value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return null === $value ? null : unserialize($value);
    }

}
