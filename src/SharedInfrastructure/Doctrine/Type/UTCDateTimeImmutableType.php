<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\Type;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;

/**
 * Overrides the built-in datetime_immutable Doctrine type so that datetimes are always
 * stored as UTC strings and re-hydrated as UTC DateTimeImmutable objects, regardless of
 * the PHP system timezone.

 */
final class UTCDateTimeImmutableType extends DateTimeImmutableType
{
    private static DateTimeZone $utc;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof DateTimeImmutable) {
            $value = $value->setTimezone(self::utc());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        if (!is_string($value)) {
            throw new InvalidFormat(sprintf('Expected string or DateTimeImmutable, got %s', get_debug_type($value)));
        }

        $dateTime = DateTimeImmutable::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::utc(),
        );

        if ($dateTime === false) {
            throw InvalidFormat::new($value, 'datetime_immutable', $platform->getDateTimeFormatString());
        }

        return $dateTime;
    }

    private static function utc(): DateTimeZone
    {
        return self::$utc ??= new DateTimeZone('UTC');
    }
}
