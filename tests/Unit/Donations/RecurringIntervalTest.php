<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateMalformedIntervalStringException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use PHPUnit\Framework\TestCase;

class RecurringIntervalTest extends TestCase
{
    public function testIntervalCreation(): void
    {
        $interval = new RecurringInterval(RecurringInterval::Monthly);
        $this->assertEquals('P1M', $interval->toString());
    }

    public function testInvalidIntervalCreation(): void
    {
        $this->expectException(DateMalformedIntervalStringException::class);
        new RecurringInterval('InvalidInterval');
    }
}
