<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
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

    private RecurringPlanAction $recurringPlanAction;

    private ShortDescription $description;

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
        $this->recurringPlanAction = RecurringPlanAction::forInit(RecurringPlanId::generate());
        $this->description = new ShortDescription('Test donation');
    }

    public function testCreate(): void
    {
        $paymentId = PaymentId::generate();

        $this->when(fn () => Donation::create(
            $this->now,
            $this->donationId,
            $this->amount,
            $this->campaignId,
            $paymentId,
            $this->description,
            $this->donorIdentity,
            $this->recurringPlanAction->recurringPlanId,
            null,
        ))->then(
            new DonationCreated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $paymentId,
                $this->description,
                $this->donorIdentity,
                $this->recurringPlanAction->recurringPlanId,
                $this->now,
            )
        );
    }

    public function testInitiate(): void
    {
        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorIdentity,
            $this->description,
        );

        $this->when(fn () => Donation::initiate(
            $this->now,
            $donationRequest,
            $this->recurringPlanAction,
        ))->then(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                $this->description,
                $this->recurringPlanAction,
                $this->donorIdentity,
            )
        );
    }

    public function testAcceptInitiated(): void
    {
        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorIdentity,
            $this->description,
        );

        $this->given(
            new DonationInitiated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                $this->gateway,
                new ShortDescription('Description'),
                $this->recurringPlanAction,
                $this->donorIdentity,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
        ))
        ->then(
            new DonationAccepted(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->recurringPlanAction->recurringPlanId,
            )
        );
    }

    public function testAcceptCreated(): void
    {
        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorIdentity,
            $this->description,
        );

        $this->given(
            new DonationCreated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                $donationRequest->paymentId,
                new ShortDescription('Description'),
                $this->donorIdentity,
                $this->recurringPlanAction->recurringPlanId,
                $this->now,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
        ))
        ->then(
            new DonationAccepted(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->recurringPlanAction->recurringPlanId,
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
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
        ))
        ->then(); // No event should be recorded
    }

    public function testAcceptFailedThrows(): void
    {
        $this->given(
            new DonationFailed(
                $this->now,
                $this->donationId,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->accept(
            $this->now,
            $this->amount,
        ))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot transition from failed to accepted');
    }

    public function testFailInitiated(): void
    {
        $donationRequest = new DonationRequest(
            $this->donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorIdentity($this->email),
            $this->description,
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
                $this->recurringPlanAction,
                new DonorIdentity($this->email),
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now))
        ->then(
            new DonationFailed(
                $this->now,
                $this->donationId,
                $this->recurringPlanAction->recurringPlanId,
            )
        );
    }

    public function testFailCreated(): void
    {
        $this->given(
            new DonationCreated(
                $this->now,
                $this->donationId,
                $this->amount,
                $this->campaignId,
                PaymentId::generate(),
                new ShortDescription('Description'),
                $this->donorIdentity,
                $this->recurringPlanAction->recurringPlanId,
                $this->now,
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now))
        ->then(
            new DonationFailed(
                $this->now,
                $this->donationId,
                $this->recurringPlanAction->recurringPlanId,
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
                new DonorIdentity($this->email),
            ),
            new DonationFailed(
                $this->now,
                $this->donationId,
                null,
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now))
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
            )
        )
        ->when(fn (Donation $donation) => $donation->fail($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot transition from accepted to failed');
    }

}
