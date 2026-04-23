<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Interval;
use Exception;
use PHPUnit\Framework\TestCase;

class IntervalTest extends TestCase
{
    public function testIntervalCreation(): void
    {
        $interval = new Interval(Interval::Monthly);
        $this->assertEquals('P1M', $interval->toString());
    }

    public function testInvalidIntervalCreation(): void
    {
        $this->expectException(Exception::class);
        new Interval('InvalidInterval');
    }
}
