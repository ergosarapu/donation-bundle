<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\LegacyPaymentNumber;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use PHPUnit\Framework\TestCase;

class PaymentValueObjectsTest extends TestCase
{
    // AccountHolderName

    public function testAccountHolderNameValid(): void
    {
        $name = new AccountHolderName('John Doe');
        $this->assertSame('John Doe', $name->value);
    }

    public function testAccountHolderNameTrimsWhitespace(): void
    {
        $name = new AccountHolderName('  John Doe  ');
        $this->assertSame('John Doe', $name->value);
    }

    public function testAccountHolderNameEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountHolderName('');
    }

    public function testAccountHolderNameWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountHolderName('   ');
    }

    public function testAccountHolderNameExactly70CharsIsAllowed(): void
    {
        $name = new AccountHolderName(str_repeat('a', 70));
        $this->assertSame(70, strlen($name->value));
    }

    public function testAccountHolderName71CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccountHolderName(str_repeat('a', 71));
    }

    // BankReference

    public function testBankReferenceValid(): void
    {
        $ref = new BankReference('REF123');
        $this->assertSame('REF123', $ref->value);
    }

    public function testBankReferenceTrimsWhitespace(): void
    {
        $ref = new BankReference('  REF123  ');
        $this->assertSame('REF123', $ref->value);
    }

    public function testBankReferenceEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BankReference('');
    }

    public function testBankReferenceWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BankReference('   ');
    }

    public function testBankReferenceExactly36CharsIsAllowed(): void
    {
        $ref = new BankReference(str_repeat('a', 36));
        $this->assertSame(36, strlen($ref->value));
    }

    public function testBankReference37CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BankReference(str_repeat('a', 37));
    }

    public function testBankReferenceMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BankReference('réf-123');
    }

    // GatewayReference

    public function testGatewayReferenceValid(): void
    {
        $ref = new GatewayReference('gw-ref-001');
        $this->assertSame('gw-ref-001', $ref->value);
    }

    public function testGatewayReferenceTrimsWhitespace(): void
    {
        $ref = new GatewayReference('  gw-ref-001  ');
        $this->assertSame('gw-ref-001', $ref->value);
    }

    public function testGatewayReferenceEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new GatewayReference('');
    }

    public function testGatewayReferenceWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new GatewayReference('   ');
    }

    public function testGatewayReferenceExactly128CharsIsAllowed(): void
    {
        $ref = new GatewayReference(str_repeat('a', 128));
        $this->assertSame(128, strlen($ref->value));
    }

    public function testGatewayReference129CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new GatewayReference(str_repeat('a', 129));
    }

    public function testGatewayReferenceMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new GatewayReference('gw-réf');
    }

    // LegacyPaymentNumber

    public function testLegacyPaymentNumberValid(): void
    {
        @$num = new LegacyPaymentNumber('PAY-0001');
        $this->assertSame('PAY-0001', $num->value);
    }

    public function testLegacyPaymentNumberTrimsWhitespace(): void
    {
        @$num = new LegacyPaymentNumber('  PAY-0001  ');
        $this->assertSame('PAY-0001', $num->value);
    }

    public function testLegacyPaymentNumberEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        @new LegacyPaymentNumber('');
    }

    public function testLegacyPaymentNumberWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        @new LegacyPaymentNumber('   ');
    }

    public function testLegacyPaymentNumberExactly128CharsIsAllowed(): void
    {
        @$num = new LegacyPaymentNumber(str_repeat('a', 128));
        $this->assertSame(128, strlen($num->value));
    }

    public function testLegacyPaymentNumber129CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        @new LegacyPaymentNumber(str_repeat('a', 129));
    }

    public function testLegacyPaymentNumberMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        @new LegacyPaymentNumber('PAY-éàü');
    }

    // PaymentCredentialValue

    public function testPaymentCredentialValueValid(): void
    {
        $val = new PaymentCredentialValue('tok_abc123');
        $this->assertSame('tok_abc123', $val->value);
    }

    public function testPaymentCredentialValueTrimsWhitespace(): void
    {
        $val = new PaymentCredentialValue('  tok_abc123  ');
        $this->assertSame('tok_abc123', $val->value);
    }

    public function testPaymentCredentialValueEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentCredentialValue('');
    }

    public function testPaymentCredentialValueWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentCredentialValue('   ');
    }

    public function testPaymentCredentialValueExactly512CharsIsAllowed(): void
    {
        $val = new PaymentCredentialValue(str_repeat('a', 512));
        $this->assertSame(512, strlen($val->value));
    }

    public function testPaymentCredentialValue513CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentCredentialValue(str_repeat('a', 513));
    }

    public function testPaymentCredentialValueMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentCredentialValue('tök_abc');
    }

    // PaymentImportSourceIdentifier

    public function testPaymentImportSourceIdentifierValid(): void
    {
        $id = new PaymentImportSourceIdentifier('import-src-001');
        $this->assertSame('import-src-001', $id->value);
    }

    public function testPaymentImportSourceIdentifierTrimsWhitespace(): void
    {
        $id = new PaymentImportSourceIdentifier('  import-src-001  ');
        $this->assertSame('import-src-001', $id->value);
    }

    public function testPaymentImportSourceIdentifierEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentImportSourceIdentifier('');
    }

    public function testPaymentImportSourceIdentifierWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentImportSourceIdentifier('   ');
    }

    public function testPaymentImportSourceIdentifierExactly128CharsIsAllowed(): void
    {
        $id = new PaymentImportSourceIdentifier(str_repeat('a', 128));
        $this->assertSame(128, strlen($id->value));
    }

    public function testPaymentImportSourceIdentifier129CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentImportSourceIdentifier(str_repeat('a', 129));
    }

    public function testPaymentImportSourceIdentifierMultibyteThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentImportSourceIdentifier('src-üöä');
    }

    // PaymentReference

    public function testPaymentReferenceValid(): void
    {
        $ref = new PaymentReference('PAY-REF-001');
        $this->assertSame('PAY-REF-001', $ref->value);
    }

    public function testPaymentReferenceTrimsWhitespace(): void
    {
        $ref = new PaymentReference('  PAY-REF-001  ');
        $this->assertSame('PAY-REF-001', $ref->value);
    }

    public function testPaymentReferenceEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentReference('');
    }

    public function testPaymentReferenceWhitespaceOnlyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentReference('   ');
    }

    public function testPaymentReferenceExactly35CharsIsAllowed(): void
    {
        $ref = new PaymentReference(str_repeat('a', 35));
        $this->assertSame(35, strlen($ref->value));
    }

    public function testPaymentReference36CharsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentReference(str_repeat('a', 36));
    }
}
