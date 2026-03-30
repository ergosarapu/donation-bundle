<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateInterval;
use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorDetails;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanCancelNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanFailNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanReActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use InvalidArgumentException;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RecurringPlanTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private RecurringPlanId $recurringPlanId;

    private RecurringPlanAction $recurringPlanActionForInit;

    private PaymentMethodId $paymentMethodId;

    private RecurringInterval $interval;

    private CampaignId $campaignId;

    private Money $amount;

    private Email $email;

    private Gateway $gateway;

    private ShortDescription $description;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');
        $this->recurringPlanId = RecurringPlanId::generate();
        $this->recurringPlanActionForInit = RecurringPlanAction::forInit();
        $this->paymentMethodId = PaymentMethodId::generate();
        $this->interval = new RecurringInterval(RecurringInterval::Monthly);
        $this->campaignId = CampaignId::generate();
        $this->amount = new Money(100, new Currency('EUR'));
        $this->email = new Email('example@example.com');
        $this->gateway = new Gateway('test');
        $this->description = new ShortDescription('Test donation');
    }

    protected function aggregateClass(): string
    {
        return RecurringPlan::class;
    }

    public function testCreate(): void
    {
        $recurringPlanId = RecurringPlanId::generate();
        $initialDonationId = DonationId::generate();
        $paymentMethodId = PaymentMethodId::generate();
        $nextRenewalTime = $this->now->add($this->interval->toDateInterval());

        $this->when(fn () => RecurringPlan::create(
            $this->now,
            $recurringPlanId,
            RecurringPlanStatus::Initiated,
            $this->interval,
            $initialDonationId,
            $this->campaignId,
            $paymentMethodId,
            $this->amount,
            $this->gateway,
            new DonorDetails($this->email),
            $nextRenewalTime,
            $this->description,
            $this->now,
        ))->then(
            new RecurringPlanCreated(
                $this->now,
                $this->now,
                $recurringPlanId,
                RecurringPlanStatus::Initiated,
                $this->interval,
                $initialDonationId,
                $this->campaignId,
                $paymentMethodId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
                $nextRenewalTime,
            )
        );
    }

    public function testCreateWithoutEmailThrows(): void
    {
        $recurringPlanId = RecurringPlanId::generate();
        $initialDonationId = DonationId::generate();
        $paymentMethodId = PaymentMethodId::generate();
        $nextRenewalTime = $this->now->add($this->interval->toDateInterval());

        $this->when(fn () => RecurringPlan::create(
            $this->now,
            $recurringPlanId,
            RecurringPlanStatus::Initiated,
            $this->interval,
            $initialDonationId,
            $this->campaignId,
            $paymentMethodId,
            $this->amount,
            $this->gateway,
            new DonorDetails(),
            $nextRenewalTime,
            $this->description,
            $this->now,
        ))->expectsException(InvalidArgumentException::class)
        ->expectsExceptionMessage('Recurring plan requires donor email');
    }

    public function testInitiate(): void
    {
        $donationId = DonationId::generate();
        $donationRequest = new DonationRequest(
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        );
        $this->when(fn () => RecurringPlan::initiate(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            $donationRequest,
            $this->interval,
        ))->then(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ));
    }

    public function testInitiateWithoutEmailThrows(): void
    {
        $donationId = DonationId::generate();
        $donationRequest = new DonationRequest(
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorDetails(),
            $this->description,
        );
        $this->when(fn () => RecurringPlan::initiate(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            $donationRequest,
            $this->interval,
        ))->expectsException(InvalidArgumentException::class)
        ->expectsExceptionMessage('Recurring plan requires donor email');
    }

    public function testSuccessfulRecurringAttemptReActivatesFailing(): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $nextRenewalTime = $this->now->add($this->interval->toDateInterval());

        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $nextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanFailing(
                $this->now,
                $this->recurringPlanId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            )
        )
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
        ))
        ->then(
            new RecurringPlanRenewalCompleted(
                $this->now,
                $this->recurringPlanId,
                $nextRenewalTime,
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $nextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            )
        );
    }

    #[DataProvider('terminalEventsProvider')]
    public function testSuccessfulRecurringAttemptDoesntChangeTerminalStatus(object $terminalEvent, DateTimeImmutable $now, string $recurringPlanId): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $nextRenewalTime = $now->add($this->interval->toDateInterval());
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());

        $this->given(
            new RecurringPlanActivated(
                $now,
                RecurringPlanId::fromString($recurringPlanId),
                $nextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            $terminalEvent
        )
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
        ))
        ->then();
    }

    /**
     * @return array<array{0: object,1: DateTimeImmutable,2: string}>
     */
    public static function terminalEventsProvider(): iterable
    {
        $now = new DateTimeImmutable('2024-02-01 00:00:00');
        $recurringPlanId = RecurringPlanId::generate();
        yield 'failed' => [new RecurringPlanFailed($now, $recurringPlanId), $now, $recurringPlanId->toString()];
        yield 'expired' => [new RecurringPlanExpired($now, $recurringPlanId), $now, $recurringPlanId->toString()];
        yield 'canceled' => [new RecurringPlanCanceled($now, $recurringPlanId), $now, $recurringPlanId->toString()];
    }

    public function testActivateActiveThrows(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            $this->paymentMethodId,
        ))->when(fn (RecurringPlan $plan) => $plan->activate($this->now, $this->paymentMethodId))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateFailedThrows(): void
    {
        $this
        ->given(new RecurringPlanFailed($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now, $this->paymentMethodId))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateExpiredThrows(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now, $this->paymentMethodId))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateCanceledThrows(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now, $this->paymentMethodId))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateInitiated(): void
    {
        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ))->when(fn (RecurringPlan $plan) => $plan->activate($this->now, $this->paymentMethodId))
        ->then(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            $this->paymentMethodId,
        ));
    }

    #[DataProvider('reActivationProvider')]
    public function testReActivateFailing(DateTimeImmutable $activatedAt, DateTimeImmutable $activationNextRenewalTime, DateTimeImmutable $reActivationTime, DateTimeImmutable $expectedNextRenewalTime): void
    {
        $this->given(
            new RecurringPlanActivated(
                $activatedAt,
                $this->recurringPlanId,
                $activationNextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanFailing(
                $activatedAt,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->reActivate($reActivationTime))
        ->then(
            new RecurringPlanActivated(
                $reActivationTime,
                $this->recurringPlanId,
                $expectedNextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            )
        );
    }

    /**
     * @return array<array{0: DateTimeImmutable,1: DateTimeImmutable,2: DateTimeImmutable,3: DateTimeImmutable}>
     */
    public static function reActivationProvider(): iterable
    {
        $now = new DateTimeImmutable('2024-02-01 00:00:00');
        $interval = (new RecurringInterval(RecurringInterval::Monthly))->toDateInterval();
        yield 're-activation before next renewal time' => [$now, $now->add($interval), $now->add($interval)->sub(DateInterval::createFromDateString('1 microsecond')), $now->add($interval)];
        yield 're-activation at next renewal time' => [$now, $now->add($interval), $now->add($interval), $now->add($interval)->add($interval)];

        // Next renewal time is pushed forward on each re-activation after it
        $reactivationTime = $now->add(DateInterval::createFromDateString('1 microsecond'));
        $expectedNextRenewalTime = $now->add($interval);
        for ($i = 1; $i <= 5; $i++) {
            $reactivationTime = $reactivationTime->add($interval);
            $expectedNextRenewalTime = $expectedNextRenewalTime->add($interval);

            yield 're-activation after next renewal time #' . $i => [
                $now,
                $now->add($interval),
                $reactivationTime,
                $expectedNextRenewalTime
            ];

        }
    }

    public function testReActivatePendingThrows(): void
    {
        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ))->when(fn (RecurringPlan $plan) => $plan->reActivate($this->now))
        ->expectsException(RecurringPlanReActivateNotAllowedException::class);
    }

    public function testReActivateActiveThrows(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            $this->paymentMethodId,
        ))->when(fn (RecurringPlan $plan) => $plan->reActivate($this->now))
        ->expectsException(RecurringPlanReActivateNotAllowedException::class);
    }

    public function testReActivateFailedThrows(): void
    {
        $this
        ->given(new RecurringPlanFailed($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->reActivate($this->now))
        ->expectsException(RecurringPlanReActivateNotAllowedException::class);
    }

    public function testReActivateExpiredThrows(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->reActivate($this->now))
        ->expectsException(RecurringPlanReActivateNotAllowedException::class);
    }

    public function testReActivateCanceledThrows(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->reActivate($this->now))
        ->expectsException(RecurringPlanReActivateNotAllowedException::class);
    }

    public function testFailedRecurringAttemptResultsFailing(): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            )
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Failed,
        ))
        ->then(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId
        ));
    }


    #[DataProvider('terminalEventsProvider')]
    public function testFailedRecurringAttemptDoesntChangeTerminalStatus(object $terminalEvent, DateTimeImmutable $now, string $recurringPlanId): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $nextRenewalTime = $now->add($this->interval->toDateInterval());
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());

        $this->given(
            new RecurringPlanActivated(
                $now,
                RecurringPlanId::fromString($recurringPlanId),
                $nextRenewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            $terminalEvent
        )
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Failed,
        ))
        ->then();
    }

    public function testCancelInitiated(): void
    {
        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId,
        ));
    }

    public function testCancelActive(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            $this->paymentMethodId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testCancelFailing(): void
    {
        $this->given(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testCancelFailedThrows(): void
    {
        $this->given(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanCancelNotAllowedException::class);
    }

    public function testCancelExpiredThrows(): void
    {
        $this->given(new RecurringPlanExpired(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanCancelNotAllowedException::class);
    }

    public function testCancelCanceledThrows(): void
    {
        $this->given(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanCancelNotAllowedException::class);
    }

    public function testFailInitiated(): void
    {
        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $this->recurringPlanActionForInit,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ));
    }

    public function testFailActive(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            $this->paymentMethodId,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testFailFailing(): void
    {
        $this->given(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }


    public function testFailFailedThrows(): void
    {
        $this->given(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->expectsException(RecurringPlanFailNotAllowedException::class);
    }

    public function testFailExpiredThrows(): void
    {
        $this->given(new RecurringPlanExpired(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->expectsException(RecurringPlanFailNotAllowedException::class);
    }

    public function testFailCanceledThrows(): void
    {
        $this->given(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->fail($this->now))
        ->expectsException(RecurringPlanFailNotAllowedException::class);
    }

    public function testInitiateRenewalOnActive(): void
    {
        $initialDonationId = DonationId::generate()->toString();
        $renewalDonationId = DonationId::generate()->toString();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalAction = RecurringPlanAction::forRenew($this->paymentMethodId);

        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::fromString($initialDonationId),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                $this->paymentMethodId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::fromString($renewalDonationId)))
        ->then(new RecurringPlanRenewalInitiated(
            $renewalTime,
            $this->recurringPlanId,
            $renewalAction,
            DonationId::fromString($renewalDonationId),
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ));
    }

    public function testInitiateRenewalOnFailing(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalDonationId = DonationId::generate()->toString();
        $renewalAction = RecurringPlanAction::forRenew($this->paymentMethodId);

        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanFailing(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::fromString($renewalDonationId)))
        ->then(new RecurringPlanRenewalInitiated(
            $renewalTime,
            $this->recurringPlanId,
            $renewalAction,
            DonationId::fromString($renewalDonationId),
            $this->campaignId,
            $this->amount,
            $this->gateway,
            new DonorDetails($this->email),
            $this->description,
        ));
    }

    public function testInitiateRenewalThrowsWhenNotDueYet(): void
    {
        $activationDonationId = DonationId::generate();
        $renewalTime = $this->now->add($this->interval->toDateInterval())->sub(DateInterval::createFromDateString('1 millisecond'));
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                $this->paymentMethodId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotDueYetException::class);
    }

    public function testInitiateRenewalThrowsWhenRenewalInProgress(): void
    {
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(LogicException::class)->expectsExceptionMessage('Donation is in progress.');
    }

    public function testInitiateRenewalThrowsWhenDonorDetailsDeleted(): void
    {
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                null,
                $this->description,
            ),
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Missing donor details. Personal info may have been deleted.');
    }

    public function testInitiateRenewalOnPendingThrows(): void
    {
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class)
        ->expectsExceptionMessage('Only active and failing recurring plans can be renewed.');
    }

    public function testInitiateRenewalOnFailedThrows(): void
    {

        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanFailed(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class)
        ->expectsExceptionMessage('Only active and failing recurring plans can be renewed.');
    }

    public function testInitiateRenewalOnExpiredThrows(): void
    {
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanExpired(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class)
        ->expectsExceptionMessage('Only active and failing recurring plans can be renewed.');
    }

    public function testInitiateRenewalOnCanceledThrows(): void
    {
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $this->recurringPlanActionForInit,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
            new RecurringPlanCanceled(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testSuccesfulRecurringAttemptCompletesRenewal(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalDonationId = DonationId::generate()->toString();
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
        ))
        ->then(new RecurringPlanRenewalCompleted(
            $renewalTime,
            $this->recurringPlanId,
            $renewalTime->add($this->interval->toDateInterval()),
        ));
    }

    public function testCompleteRecurringAttemptRenewalNotInProgressIgnored(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                $this->paymentMethodId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::generate(),
            DonationStatus::Accepted,
        ))
        ->then();
    }

    public function testCompleteRecurringAttemptRenewalIdMismatchIgnored(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::generate(),
            DonationStatus::Accepted,
        ))
        ->then();
    }

    #[DataProvider('unsupportedDonationStatusProvider')]
    public function testCompleteRecurringAttemptNotSupportedDonationStatusThrows(DonationStatus $status): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalAction = RecurringPlanAction::forRenew(PaymentMethodId::generate());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                $this->paymentMethodId,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $renewalAction,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                new DonorDetails($this->email),
                $this->description,
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::fromString($renewalDonationId),
            $status,
        ))
        ->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Unsupported donation status: ' . $status->value);
    }

    /**
     * @return array<array{0: DonationStatus}>
     */
    public static function unsupportedDonationStatusProvider(): array
    {
        return array_map(
            fn (DonationStatus $status) => [$status],
            array_filter(
                DonationStatus::cases(),
                fn (DonationStatus $status) => !in_array($status, [DonationStatus::Accepted, DonationStatus::Failed])
            )
        );
    }
}
