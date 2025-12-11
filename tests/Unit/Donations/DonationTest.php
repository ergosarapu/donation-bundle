<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringToken;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

class DonationTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private CampaignId $campaignId;

    private Money $amount;

    private Email $email;

    private Gateway $gateway;

    private DonationId $donationId;

    private DonorIdentity $donorIdentity;

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
        $this->donorIdentity = new DonorIdentity($this->email);
        $this->gateway = new Gateway('test');
        $this->donationId = DonationId::generate();
    }

    public function testInitiate(): void
    {
        $recurringPlanId = RecurringPlanId::generate();
        $recurringToken = new RecurringToken('token123');

        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorIdentity,
        );

        $this->when(fn () => Donation::initiate(
            $this->now,
            $donationRequest,
            $recurringPlanId,
            $recurringToken,
        ))->then(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                new ShortDescription('TODO: Add description'),
                $recurringPlanId,
                $recurringToken,
                $this->donorIdentity,
            )
        );
    }

    public function testAccept(): void
    {
        $recurringPlanId = RecurringPlanId::generate();
        $recurringToken = new RecurringToken('token456');
        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorIdentity,
        );

        $this->given(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                new ShortDescription('TODO: Add description'),
                $recurringPlanId,
                null,
                $this->donorIdentity,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
            $recurringToken
        ))
        ->then(
            new DonationAccepted(
                $this->now,
                $this->donationId,
                $this->amount,
                $recurringPlanId,
                $recurringToken,
            )
        );
    }

    public function testAcceptIsIdempotent(): void
    {
        $this->given(
            new DonationAccepted(
                $this->now,
                $this->donationId,
                $this->amount,
                null,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
            null
        ))
        ->then(); // No event should be recorded
    }

    public function testAcceptFailedThrows(): void
    {
        $this->given(
            new DonationFailed(
                $this->now,
                $this->donationId,
                false,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
            null
        ))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot transition from failed to accepted');
    }

    public function testFail(): void
    {
        $recurringPlanId = RecurringPlanId::generate();

        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorIdentity($this->email),
        );

        $this->given(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                new ShortDescription('TODO: Add description'),
                $recurringPlanId,
                null,
                new DonorIdentity($this->email),
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now, true))
        ->then(
            new DonationFailed(
                $this->now,
                $this->donationId,
                true,
                $recurringPlanId,
            )
        );
    }

    public function testFailIsIdempotent(): void
    {
        $this->given(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                PaymentId::generate(),
                $this->gateway,
                new ShortDescription('TODO: Add description'),
                null,
                null,
                new DonorIdentity($this->email),
            ),
            new DonationFailed(
                $this->now,
                $this->donationId,
                false,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now, false))
        ->then(); // No event should be recorded
    }

    public function testFailAcceptedThrows(): void
    {
        $this->given(
            new DonationAccepted(
                $this->now,
                $this->donationId,
                $this->amount,
                null,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now, false))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot transition from accepted to failed');
    }

}
