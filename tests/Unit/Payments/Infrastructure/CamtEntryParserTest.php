<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Infrastructure;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\CamtEntryParser;
use Genkgo\Camt\Config;
use Genkgo\Camt\Reader;
use PHPUnit\Framework\TestCase;

class CamtEntryParserTest extends TestCase
{
    private function getEntryParserForFile(string $filename): CamtEntryParser
    {
        $reader = new Reader(Config::getDefault());
        $message = $reader->readFile($filename);
        $statements = $message->getRecords();
        $entry = $statements[0]->getEntries()[0];
        return new CamtEntryParser($entry);
    }

    public function testOrgDebtor(): void
    {
        $parser = $this->getEntryParserForFile(__DIR__.'/Fixtures/single_entry_org_debtor.camt.xml');
        $this->assertEquals('GB94BARC10201530093459', $parser->getIban()?->value);
        $this->assertEquals('11111111', $parser->getOrganisationRegCode()?->value);
        $this->assertEquals('Test Company OÜ', $parser->getAccountHolderName()?->value);
        $this->assertEquals('ANONREF1-111111111', $parser->getAccountServicerReference()->value);
        $this->assertEquals(10000, $parser->getAmount()->amount());
        $this->assertEquals('EUR', $parser->getAmount()->currency()->code());
        $this->assertEquals('ANONFEE2', $parser->getBic()?->value);
        $this->assertEquals(new DateTimeImmutable('2025-11-24'), $parser->getBookingDate());
        $this->assertEquals('Donation', $parser->getDescription()?->toString());
        $this->assertEquals('GB94BARC10201530093459', $parser->getIban()?->value);
        $this->assertNull($parser->getNationalIdCode());
        $this->assertEquals('11223344556677', $parser->getPaymentReference()?->value);
    }

    public function testPrivateDebtor(): void
    {
        $parser = $this->getEntryParserForFile(__DIR__.'/Fixtures/single_entry_private_debtor.camt.xml');
        $this->assertEquals('GB94BARC10201530093459', $parser->getIban()?->value);
        $this->assertNull($parser->getOrganisationRegCode());
        $this->assertEquals('Mati Karu', $parser->getAccountHolderName()?->value);
        $this->assertEquals('ANONREF1-111111112', $parser->getAccountServicerReference()->value);
        $this->assertEquals(10000, $parser->getAmount()->amount());
        $this->assertEquals('EUR', $parser->getAmount()->currency()->code());
        $this->assertEquals('ANONFEE2', $parser->getBic()?->value);
        $this->assertEquals(new DateTimeImmutable('2025-11-24'), $parser->getBookingDate());
        $this->assertEquals('Donation', $parser->getDescription()?->toString());
        $this->assertEquals('GB94BARC10201530093459', $parser->getIban()?->value);
        $this->assertEquals('39876543210', $parser->getNationalIdCode()?->value);
        $this->assertEquals('11223344556677', $parser->getPaymentReference()?->value);
    }
}
