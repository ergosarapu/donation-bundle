<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

class DonationTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private CampaignId $campaignId;

    private Money $amount;

    private Email $email;

    private Gateway $gateway;

    protected function aggregateClass(): string
    {
        return Donation::class;
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');
        $this->campaignId = CampaignId::generate();
        $this->amount = new Money(100, new Currency('EUR'));
        $this->email = new Email('example@example.com');
        $this->gateway = new Gateway('test');
    }

    public function testInitiate(): void
    {
        $donationId = DonationId::generate();
        $donationRequest = new DonationRequest(
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->email,
        );

        $this->when(fn () => Donation::initiate(
            $this->now,
            $donationRequest,
        ))->then(
            new DonationInitiated(
                $this->now,
                $donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                new ShortDescription('TODO: Add description'),
                null,
                null,
                null,
                $this->email,
                null,
            )
        );

    }
}
