<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateInterval;
use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Aggregate\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkCanceledNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkFailedNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkFailingNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalAlreadyInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
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

    public function testActivateInitiated(): void
    {
        $activationDonationId = DonationId::generate();

        $this->given(new RecurringPlanInitiated(
            $this->now,
            $this->recurringPlanId,
            $activationDonationId,
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))
        ->when(fn (RecurringPlan $donation) => $donation->activate($this->now))
        ->then(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ));
    }

    public function testActivateActive(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringPlan $donation) => $donation->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateFailed(): void
    {
        $this
        ->given(new RecurringPlanFailed($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateExpired(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }

    public function testActivateCanceled(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->activate($this->now))
        ->expectsException(RecurringPlanActivateNotAllowedException::class);
    }
    #[DataProvider('reActivationProvider')]
    public function testActivateFailing(DateTimeImmutable $activatedAt, DateTimeImmutable $activationNextRenewalTime, DateTimeImmutable $reActivationTime, DateTimeImmutable $expectedNextRenewalTime): void
    {
        $this->given(
            new RecurringPlanActivated(
                $activatedAt,
                $this->recurringPlanId,
                $activationNextRenewalTime,
                $this->interval,
            ),
            new RecurringPlanFailing(
                $activatedAt,
                $this->recurringPlanId
            )
        )->when(fn (RecurringPlan $donation) => $donation->activate($reActivationTime))
        ->then(
            new RecurringPlanActivated(
                $reActivationTime,
                $this->recurringPlanId,
                $expectedNextRenewalTime,
                $this->interval,
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

    public function testMarkActiveAsFailing(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->then(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkFailingAsFailing(): void
    {
        $this->given(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringPlanMarkFailingNotAllowedException::class);
    }

    public function testMarkInitiatedAsFailing(): void
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
        ))
        ->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringPlanMarkFailingNotAllowedException::class);
    }

    public function testMarkFailedAsFailing(): void
    {
        $this
        ->given(new RecurringPlanFailed($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringPlanMarkFailingNotAllowedException::class);
    }

    public function testMarkExpiredAsFailing(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringPlanMarkFailingNotAllowedException::class);
    }

    public function testMarkCanceledAsFailing(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringPlanMarkFailingNotAllowedException::class);
    }

    public function testMarkActiveAsFailed(): void
    {
        $this->given(new RecurringPlanActivated(
            $this->now,
            $this->recurringPlanId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkFailingAsFailed(): void
    {
        $this->given(new RecurringPlanFailing(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkPendingAsFailed(): void
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
        ))->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->then(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId
        ));
    }

    public function testMarkFailedAsFailed(): void
    {
        $this->given(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringPlanMarkFailedNotAllowedException::class);
    }

    public function testMarkExpiredAsFailed(): void
    {
        $this
        ->given(new RecurringPlanExpired($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringPlanMarkFailedNotAllowedException::class);
    }

    public function testMarkCanceledAsFailed(): void
    {
        $this
        ->given(new RecurringPlanCanceled($this->now, $this->recurringPlanId))
        ->when(fn (RecurringPlan $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringPlanMarkFailedNotAllowedException::class);
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
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
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
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
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
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
        ->then(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId
        ));
    }


    public function testMarkFailedAsCanceled(): void
    {
        $this->given(new RecurringPlanFailed(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testMarkExpiredAsCanceled(): void
    {
        $this->given(new RecurringPlanExpired(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testMarkCanceledAsCanceled(): void
    {
        $this->given(new RecurringPlanCanceled(
            $this->now,
            $this->recurringPlanId,
        ))->when(fn (RecurringPlan $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringPlanMarkCanceledNotAllowedException::class);
    }

    public function testInitiateRenewal(): void
    {
        $activationDonationId = DonationId::generate();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
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
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($renewalTime))
        ->then(new RecurringPlanRenewalInitiated(
            $renewalTime,
            $this->recurringPlanId,
            $activationDonationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->email,
        ));
    }

    public function testInitiateRenewalNotDueYet(): void
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
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($renewalTime))
        ->expectsException(RecurringPlanRenewalNotDueYetException::class);
    }

    public function testInitiateRenewalAlreadyInitiated(): void
    {
        $activationDonationId = DonationId::generate();
        $this->given(
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringPlanRenewalAlreadyInitiatedException::class);
    }

    public function testInitiateRenewalOnPending(): void
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
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnFailed(): void
    {

        $this->given(
            new RecurringPlanFailed(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnFailing(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
            ),
            new RecurringPlanFailing(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($renewalTime))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnExpired(): void
    {
        $this->given(
            new RecurringPlanExpired(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnCanceled(): void
    {
        $this->given(
            new RecurringPlanCanceled(
                $this->now,
                $this->recurringPlanId,
            )
        )->when(fn (RecurringPlan $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringPlanRenewalNotAllowedException::class);
    }

    public function testCompleteRenewal(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $activationDonationId = DonationId::generate();
        $this->given(
            new RecurringPlanRenewalInitiated(
                $this->now,
                $this->recurringPlanId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
            ),
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
            )
        )->when(fn (RecurringPlan $donation) => $donation->completeRenewal($renewalTime))
        ->then(new RecurringPlanRenewalCompleted(
            $renewalTime,
            $this->recurringPlanId,
            $renewalTime->add($this->interval->toDateInterval()),
        ));
    }

    public function testCompleteRenewalNotInitiated(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringPlanActivated(
                $this->now,
                $this->recurringPlanId,
                $renewalTime,
                $this->interval,
            )
        )->when(fn (RecurringPlan $donation) => $donation->completeRenewal($renewalTime))
        ->expectsException(RecurringPlanRenewalNotInitiatedException::class);
    }
}
