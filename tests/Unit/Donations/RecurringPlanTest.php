<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateInterval;
use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanMarkCanceledNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringToken;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use InvalidArgumentException;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RecurringPlanTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private RecurringPlanId $recurringPlanId;

    private RecurringInterval $interval;

    private CampaignId $campaignId;

    private Money $amount;

    private Email $email;

    private Gateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');
        $this->recurringPlanId = RecurringPlanId::generate();
        $this->interval = new RecurringInterval(RecurringInterval::Monthly);
        $this->campaignId = CampaignId::generate();
        $this->amount = new Money(100, new Currency('EUR'));
        $this->email = new Email('example@example.com');
        $this->gateway = new Gateway('test');
    }

    protected function aggregateClass(): string
    {
        return RecurringPlan::class;
    }

    public function testInitiate(): void
    {
        $donationId = DonationId::generate();
        $this->when(fn () => RecurringPlan::initiate(
            $this->now,
            $this->recurringPlanId,
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))->then(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $donationId,
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ));
    }

    public function testSuccessfulRecurringAttemptActivatesInitiated(): void
    {
        $activationDonationId = DonationId::generate()->toString();
        $recurringToken = RecurringToken::fromString('recurring-token-123')->toString();

        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            DonationId::fromString($activationDonationId),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($activationDonationId),
            DonationStatus::Accepted,
            RecurringToken::fromString($recurringToken)
        ))
        ->then(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            RecurringToken::fromString($recurringToken),
        ));
    }

    public function testSuccessfulRecurringAttemptReActivatesFailing(): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $recurringToken = RecurringToken::fromString('recurring-token-123')->toString();
        $nextRenewalTime = $this->now->add($this->interval->toDateInterval());

        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $nextRenewalTime,
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            ),
            new RecurringPlanFailing(
                $this->now,
                $this->recurringPlanId
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            )
        )
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
            RecurringToken::fromString($recurringToken)
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
                RecurringToken::fromString($recurringToken),
            )
        );
    }

    #[DataProvider('terminalEventsProvider')]
    public function testSuccessfulRecurringAttemptDoesntChangeTerminalStatus(object $terminalEvent, DateTimeImmutable $now, string $recurringPlanId): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $recurringToken = RecurringToken::fromString('recurring-token-123')->toString();
        $nextRenewalTime = $now->add($this->interval->toDateInterval());

        $this->given(
            new RecurringPlanActivated(
                $now,
                RecurringPlanId::fromString($recurringPlanId),
                $nextRenewalTime,
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            ),
            new RecurringPlanRenewalInitiated(
                $now,
                RecurringPlanId::fromString($recurringPlanId),
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            ),
            $terminalEvent
        )
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
            RecurringToken::fromString($recurringToken)
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

    public function testSuccessfulRecurringAttemptWithoutTokenFailsInitiated(): void
    {
        $activationDonationId = DonationId::generate()->toString();

        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            DonationId::fromString($activationDonationId),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))
        ->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($activationDonationId),
            DonationStatus::Accepted,
            null
        ))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ));
    }

    public function testActivateActiveThrows(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            null,
        ))->when(fn (RecurringPlan $plan) => $plan->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateFailedThrows(): void
    {
        $this
        ->given(new RecurringPlanFailed($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateExpiredThrows(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateCanceledThrows(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $plan) => $plan->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
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
                null,
            ),
            new RecurringPlanFailing(
                $activatedAt,
                $this->recurringPlanId
            )
        )->when(fn (RecurringPlan $plan) => $plan->activate($reActivationTime))
        ->then(
            new RecurringPlanActivated(
                $reActivationTime,
                $this->recurringPlanId,
                $expectedNextRenewalTime,
                $this->interval,
                null,
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

    public function testFailedRecurringAttemptResultsFailing(): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                null,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            )
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Failed,
            null,
            true,
        ))
        ->then(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testFailedRecurringAttemptResultsFailed(): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                null,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            )
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $this->now,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Failed,
            null,
            false,
        ))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkPendingAsCanceled(): void
    {
        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkActiveAsCanceled(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
            null,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkFailingAsCanceled(): void
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


    public function testMarkFailedAsCanceledThrows(): void
    {
        $this->given(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testMarkExpiredAsCanceledThrows(): void
    {
        $this->given(new RecurringPlanExpired(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testMarkCanceledAsCanceledThrows(): void
    {
        $this->given(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $plan) => $plan->cancel($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testInitiateRenewalOnActive(): void
    {
        $activationDonationId = DonationId::generate()->toString();
        $renewalDonationId = DonationId::generate()->toString();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $recurringToken = RecurringToken::fromString('recurring-token-123')->toString();
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($activationDonationId),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString($recurringToken),
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::fromString($renewalDonationId)))
        ->then(new RecurringPlanRenewalInitiated(
            $renewalTime,
            $this->recurringPlanId,
            DonationId::fromString($renewalDonationId),
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->email,
            RecurringToken::fromString($recurringToken),
        ));
    }

    public function testInitiateRenewalOnFailing(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $renewalDonationId = DonationId::generate()->toString();
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            ),
            new RecurringPlanFailing(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::fromString($renewalDonationId)))
        ->then(new RecurringPlanRenewalInitiated(
            $renewalTime,
            $this->recurringPlanId,
            DonationId::fromString($renewalDonationId),
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->email,
            RecurringToken::fromString('recurring-token-123'),
        ));
    }

    public function testInitiateRenewalThrowsWhenMissingToken(): void
    {
        $activationDonationId = DonationId::generate();
        $renewalTime = $this->now->add($this->interval->toDateInterval())->sub(DateInterval::createFromDateString('1 millisecond'));
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                null,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class)->expectsExceptionMessage('Missing recurring token.');
    }

    public function testInitiateRenewalThrowsWhenNotDueYet(): void
    {
        $activationDonationId = DonationId::generate();
        $renewalTime = $this->now->add($this->interval->toDateInterval())->sub(DateInterval::createFromDateString('1 millisecond'));
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($renewalTime, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotDueYetException::class);
    }

    public function testInitiateRenewalThrowsWhenDonationInProgress(): void
    {
        $activationDonationId = DonationId::generate();
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(LogicException::class)->expectsExceptionMessage('Donation is in progress.');
    }

    public function testInitiateRenewalOnPendingThrows(): void
    {
        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(LogicException::class);
    }

    public function testInitiateRenewalOnFailedThrows(): void
    {

        $this->given(
            new RecurringPlanInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringPlanFailed(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(LogicException::class);
    }

    public function testInitiateRenewalOnExpiredThrows(): void
    {
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
            ),
            new RecurringPlanExpired(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $plan) => $plan->initiateRenewal($this->now, DonationId::generate()))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnCanceledThrows(): void
    {
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
                RecurringToken::fromString('recurring-token-123'),
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
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                null,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::fromString($renewalDonationId),
            DonationStatus::Accepted,
            null
        ))
        ->then(new RecurringPlanRenewalCompleted(
            $renewalTime,
            $this->recurringPlanId,
            $renewalTime->add($this->interval->toDateInterval()),
        ));
    }

    public function testCompleteRecurringAttemptNotInProgressThrows(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                null,
            )
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::generate(),
            DonationStatus::Accepted,
            null
        ))
        ->expectsException(LogicException::class);
    }

    public function testCompleteRecurringAttemptMismatchInProgressThrows(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                null,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::generate(),
            DonationStatus::Accepted,
            null
        ))
        ->expectsException(LogicException::class);
    }

    #[DataProvider('unsupportedDonationStatusProvider')]
    public function testCompleteRecurringAttemptNotSupportedDonationStatusThrows(DonationStatus $status): void
    {
        $renewalDonationId = DonationId::generate()->toString();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
                null,
            ),
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                DonationId::fromString($renewalDonationId),
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
                RecurringToken::fromString('recurring-token-123'),
            ),
        )->when(fn (RecurringPlan $plan) => $plan->completeRecurringAttempt(
            $renewalTime,
            DonationId::fromString($renewalDonationId),
            $status,
            null
        ))
        ->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Unsupported donation status: ' . $status->value)
        ;
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
