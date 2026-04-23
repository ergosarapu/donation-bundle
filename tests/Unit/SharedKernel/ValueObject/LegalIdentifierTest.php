<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\SharedKernel\ValueObject;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Country;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\IdentifierType;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use PHPUnit\Framework\TestCase;

final class LegalIdentifierTest extends TestCase
{
    public function testValidNationalIdCode(): void
    {
        $id = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $this->assertSame('38001085718', $id->value);
        $this->assertEquals(new Country('EE'), $id->country);
        $this->assertSame(IdentifierType::NationalIdNumber, $id->identifierType);
    }

    public function testValidOrganisationRegCode(): void
    {
        $id = LegalIdentifier::organisationRegNumber('80123455', new Country('EE'));
        $this->assertSame('80123455', $id->value);
        $this->assertEquals(new Country('EE'), $id->country);
        $this->assertSame(IdentifierType::OrganisationRegNumber, $id->identifierType);
    }

    public function testValidNationalIdCodeUsingZeroFallbackChecksum(): void
    {
        $id = LegalIdentifier::nationalIdNumber('10001010080', new Country('EE'));
        $this->assertSame('10001010080', $id->value);
    }

    public function testValidNationalIdCodeUsingSecondChecksumRound(): void
    {
        $id = LegalIdentifier::nationalIdNumber('10001010214', new Country('EE'));
        $this->assertSame('10001010214', $id->value);
    }

    public function testValidRegCodeUsingSecondChecksumRound(): void
    {
        $id = LegalIdentifier::organisationRegNumber('10000062', new Country('EE'));
        $this->assertSame('10000062', $id->value);
    }

    public function testValidRegCodeUsingZeroFallbackChecksum(): void
    {
        $id = LegalIdentifier::organisationRegNumber('10000640', new Country('EE'));
        $this->assertSame('10000640', $id->value);
    }

    public function testTrimsWhitespace(): void
    {
        $id = LegalIdentifier::nationalIdNumber('  38001085718  ');
        $this->assertSame('38001085718', $id->value);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::nationalIdNumber('');
    }

    public function testWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::organisationRegNumber('   ');
    }

    public function testMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::nationalIdNumber('3800108571ä');
    }

    public function testCountryIsOptional(): void
    {
        $id = LegalIdentifier::nationalIdNumber('12345678901');
        $this->assertSame('12345678901', $id->value);
        $this->assertNull($id->country);
    }

    public function testToStringIncludesCountryWhenPresent(): void
    {
        $id = LegalIdentifier::organisationRegNumber('80123455', new Country('EE'));
        $this->assertSame('EE 80123455', (string) $id);
    }

    public function testToStringReturnsValueWhenCountryMissing(): void
    {
        $id = LegalIdentifier::organisationRegNumber('not-validated');
        $this->assertSame('not-validated', (string) $id);
    }

    public function testEqualsWhenSame(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $b = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $this->assertTrue($a->equals($b));
    }

    public function testEqualsWhenDifferentType(): void
    {
        $a = LegalIdentifier::nationalIdNumber('80123455');
        $b = LegalIdentifier::organisationRegNumber('80123455');
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenValueDiffers(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $b = LegalIdentifier::nationalIdNumber('48901085713', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWithNull(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $this->assertFalse($a->equals(null));
    }

    public function testEqualsWhenCountryDiffers(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $b = LegalIdentifier::nationalIdNumber('38001085718', new Country('LV'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenOnlyOtherHasCountry(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718');
        $b = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $this->assertFalse($a->equals($b));
    }

    public function testEqualsWhenSameWithoutCountry(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718');
        $b = LegalIdentifier::nationalIdNumber('38001085718');
        $this->assertTrue($a->equals($b));
    }

    public function testInvalidEstonianNationalIdCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::nationalIdNumber('12345678901', new Country('EE'));
    }

    public function testMalformedEstonianNationalIdCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::nationalIdNumber('98001085718', new Country('EE'));
    }

    public function testShortEstonianNationalIdCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::nationalIdNumber('3800108571', new Country('EE'));
    }

    public function testInvalidEstonianOrganisationRegCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::organisationRegNumber('80123456', new Country('EE'));
    }

    public function testMalformedEstonianOrganisationRegCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::organisationRegNumber('20123456', new Country('EE'));
    }

    public function testShortEstonianOrganisationRegCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegalIdentifier::organisationRegNumber('8012345', new Country('EE'));
    }

    public function testUnsupportedCountrySkipsValidation(): void
    {
        $id = LegalIdentifier::organisationRegNumber('invalid value ?.:;', new Country('LV'));
        $this->assertSame('LV', $id->country?->value);
    }

    public function testHasSameValueAsRequiresSameType(): void
    {
        $a = LegalIdentifier::nationalIdNumber('38001085718', new Country('EE'));
        $b = LegalIdentifier::nationalIdNumber('38001085718');
        $c = LegalIdentifier::organisationRegNumber('38001085718');
        $d = LegalIdentifier::nationalIdNumber('48901085713', new Country('EE'));

        $this->assertTrue($a->hasSameValueAs($b));
        $this->assertFalse($a->hasSameValueAs($c));
        $this->assertFalse($a->hasSameValueAs($d));
        $this->assertFalse($a->hasSameValueAs(null));
    }
}
