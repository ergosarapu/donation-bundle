<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Domain;

use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use PHPUnit\Framework\TestCase;

final class ClaimSourceTest extends TestCase
{
    private const UUID = '018f4c8e-1234-7000-8000-000000000001';

    public function testCreatesPaymentSource(): void
    {
        $source = ClaimSource::create(ClaimSourceContext::Payment, self::UUID, 'payment');

        $this->assertSame(ClaimSourceContext::Payment, $source->context);
        $this->assertSame('payment', $source->type);
        $this->assertSame(self::UUID, $source->id);
    }

    public function testCreatesSourceWithoutType(): void
    {
        $source = ClaimSource::create(ClaimSourceContext::Payment, self::UUID);

        $this->assertSame(ClaimSourceContext::Payment, $source->context);
        $this->assertNull($source->type);
        $this->assertSame(self::UUID, $source->id);
    }

    public function testCreatesDonationSource(): void
    {
        $source = ClaimSource::create(ClaimSourceContext::Donation, self::UUID, 'donation');

        $this->assertSame(ClaimSourceContext::Donation, $source->context);
        $this->assertSame('donation', $source->type);
        $this->assertSame(self::UUID, $source->id);
    }
}
