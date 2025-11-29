<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Donations;

use DateInterval;
use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationMarkCanceledNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationMarkFailedNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationMarkFailingNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationRenewalAlreadyInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringDonationRenewalNotInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RecurringDonationTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    private RecurringDonationId $recurringDonationId;

    private RecurringInterval $interval;

    private CampaignId $campaignId;

    private Money $amount;

    private Email $email;

    private Gateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');
        $this->recurringDonationId = RecurringDonationId::generate();
        $this->interval = new RecurringInterval(RecurringInterval::Monthly);
        $this->campaignId = CampaignId::generate();
        $this->amount = new Money(100, new Currency('EUR'));
        $this->email = new Email('example@example.com');
        $this->gateway = new Gateway('test');
    }

    protected function aggregateClass(): string
    {
        return RecurringDonation::class;
    }

    public function testActivateInitiated(): void
    {
        $activationDonationId = DonationId::generate();

        $this->given(new RecurringDonationInitiated(
            $this->now,
            $this->recurringDonationId,
            $activationDonationId,
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))
        ->when(fn (RecurringDonation $donation) => $donation->activate($this->now))
        ->then(new RecurringDonationActivated(
            $this->now,
            $this->recurringDonationId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ));
    }

    public function testActivateActive(): void
    {
        $this->given(new RecurringDonationActivated(
            $this->now,
            $this->recurringDonationId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringDonation $donation) => $donation->activate($this->now))
        ->expectsException(RecurringDonationActivateNotAllowedException::class);
    }

    public function testActivateFailed(): void
    {
        $this
        ->given(new RecurringDonationFailed($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->activate($this->now))
        ->expectsException(RecurringDonationActivateNotAllowedException::class);
    }

    public function testActivateExpired(): void
    {
        $this
        ->given(new RecurringDonationExpired($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->activate($this->now))
        ->expectsException(RecurringDonationActivateNotAllowedException::class);
    }

    public function testActivateCanceled(): void
    {
        $this
        ->given(new RecurringDonationCanceled($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->activate($this->now))
        ->expectsException(RecurringDonationActivateNotAllowedException::class);
    }
    #[DataProvider('reActivationProvider')]
    public function testActivateFailing(DateTimeImmutable $activatedAt, DateTimeImmutable $activationNextRenewalTime, DateTimeImmutable $reActivationTime, DateTimeImmutable $expectedNextRenewalTime): void
    {
        $this->given(
            new RecurringDonationActivated(
                $activatedAt,
                $this->recurringDonationId,
                $activationNextRenewalTime,
                $this->interval,
            ),
            new RecurringDonationFailing(
                $activatedAt,
                $this->recurringDonationId
            )
        )->when(fn (RecurringDonation $donation) => $donation->activate($reActivationTime))
        ->then(
            new RecurringDonationActivated(
                $reActivationTime,
                $this->recurringDonationId,
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
        $this->given(new RecurringDonationActivated(
            $this->now,
            $this->recurringDonationId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->then(new RecurringDonationFailing(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkFailingAsFailing(): void
    {
        $this->given(new RecurringDonationFailing(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringDonationMarkFailingNotAllowedException::class);
    }

    public function testMarkInitiatedAsFailing(): void
    {
        $this->given(new RecurringDonationInitiated(
            $this->now,
            $this->recurringDonationId,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))
        ->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringDonationMarkFailingNotAllowedException::class);
    }

    public function testMarkFailedAsFailing(): void
    {
        $this
        ->given(new RecurringDonationFailed($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringDonationMarkFailingNotAllowedException::class);
    }

    public function testMarkExpiredAsFailing(): void
    {
        $this
        ->given(new RecurringDonationExpired($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringDonationMarkFailingNotAllowedException::class);
    }

    public function testMarkCanceledAsFailing(): void
    {
        $this
        ->given(new RecurringDonationCanceled($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->markFailing($this->now))
        ->expectsException(RecurringDonationMarkFailingNotAllowedException::class);
    }

    public function testMarkActiveAsFailed(): void
    {
        $this->given(new RecurringDonationActivated(
            $this->now,
            $this->recurringDonationId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->then(new RecurringDonationFailed(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkFailingAsFailed(): void
    {
        $this->given(new RecurringDonationFailing(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->then(new RecurringDonationFailed(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkPendingAsFailed(): void
    {
        $this->given(new RecurringDonationInitiated(
            $this->now,
            $this->recurringDonationId,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->then(new RecurringDonationFailed(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkFailedAsFailed(): void
    {
        $this->given(new RecurringDonationFailed(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringDonationMarkFailedNotAllowedException::class);
    }

    public function testMarkExpiredAsFailed(): void
    {
        $this
        ->given(new RecurringDonationExpired($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringDonationMarkFailedNotAllowedException::class);
    }

    public function testMarkCanceledAsFailed(): void
    {
        $this
        ->given(new RecurringDonationCanceled($this->now, $this->recurringDonationId))
        ->when(fn (RecurringDonation $donation) => $donation->markFailed($this->now))
        ->expectsException(RecurringDonationMarkFailedNotAllowedException::class);
    }

    public function testMarkPendingAsCanceled(): void
    {
        $this->given(new RecurringDonationInitiated(
            $this->now,
            $this->recurringDonationId,
            DonationId::generate(),
            $this->campaignId,
            $this->amount,
            $this->interval,
            $this->email,
            $this->gateway
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->then(new RecurringDonationCanceled(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkActiveAsCanceled(): void
    {
        $this->given(new RecurringDonationActivated(
            $this->now,
            $this->recurringDonationId,
            $this->now->add($this->interval->toDateInterval()),
            $this->interval,
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->then(new RecurringDonationCanceled(
            $this->now,
            $this->recurringDonationId
        ));
    }

    public function testMarkFailingAsCanceled(): void
    {
        $this->given(new RecurringDonationFailing(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->then(new RecurringDonationCanceled(
            $this->now,
            $this->recurringDonationId
        ));
    }


    public function testMarkFailedAsCanceled(): void
    {
        $this->given(new RecurringDonationFailed(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringDonationMarkCanceledNotAllowedException::class);
    }

    public function testMarkExpiredAsCanceled(): void
    {
        $this->given(new RecurringDonationExpired(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringDonationMarkCanceledNotAllowedException::class);
    }

    public function testMarkCanceledAsCanceled(): void
    {
        $this->given(new RecurringDonationCanceled(
            $this->now,
            $this->recurringDonationId,
        ))->when(fn (RecurringDonation $donation) => $donation->markCanceled($this->now))
        ->expectsException(RecurringDonationMarkCanceledNotAllowedException::class);
    }

    public function testInitiateRenewal(): void
    {
        $activationDonationId = DonationId::generate();
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringDonationInitiated(
                $this->now,
                $this->recurringDonationId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringDonationActivated(
                $this->now,
                $this->recurringDonationId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($renewalTime))
        ->then(new RecurringDonationRenewalInitiated(
            $renewalTime,
            $this->recurringDonationId,
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
            new RecurringDonationInitiated(
                $this->now,
                $this->recurringDonationId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            ),
            new RecurringDonationActivated(
                $this->now,
                $this->recurringDonationId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($renewalTime))
        ->expectsException(RecurringDonationRenewalNotDueYetException::class);
    }

    public function testInitiateRenewalAlreadyInitiated(): void
    {
        $activationDonationId = DonationId::generate();
        $this->given(
            new RecurringDonationRenewalInitiated(
                $this->now,
                $this->recurringDonationId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringDonationRenewalAlreadyInitiatedException::class);
    }

    public function testInitiateRenewalOnPending(): void
    {
        $this->given(
            new RecurringDonationInitiated(
                $this->now,
                $this->recurringDonationId,
                DonationId::generate(),
                $this->campaignId,
                $this->amount,
                $this->interval,
                $this->email,
                $this->gateway
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringDonationRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnFailed(): void
    {

        $this->given(
            new RecurringDonationFailed(
                $this->now,
                $this->recurringDonationId,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringDonationRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnFailing(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringDonationActivated(
                $this->now,
                $this->recurringDonationId,
                $this->now->add($this->interval->toDateInterval()),
                $this->interval,
            ),
            new RecurringDonationFailing(
                $this->now,
                $this->recurringDonationId,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($renewalTime))
        ->expectsException(RecurringDonationRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnExpired(): void
    {
        $this->given(
            new RecurringDonationExpired(
                $this->now,
                $this->recurringDonationId,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringDonationRenewalNotAllowedException::class);
    }

    public function testInitiateRenewalOnCanceled(): void
    {
        $this->given(
            new RecurringDonationCanceled(
                $this->now,
                $this->recurringDonationId,
            )
        )->when(fn (RecurringDonation $donation) => $donation->initiateRenewal($this->now))
        ->expectsException(RecurringDonationRenewalNotAllowedException::class);
    }

    public function testCompleteRenewal(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $activationDonationId = DonationId::generate();
        $this->given(
            new RecurringDonationRenewalInitiated(
                $this->now,
                $this->recurringDonationId,
                $activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->email,
            ),
            new RecurringDonationActivated(
                $this->now,
                $this->recurringDonationId,
                $renewalTime,
                $this->interval,
            )
        )->when(fn (RecurringDonation $donation) => $donation->completeRenewal($renewalTime))
        ->then(new RecurringDonationRenewalCompleted(
            $renewalTime,
            $this->recurringDonationId,
            $renewalTime->add($this->interval->toDateInterval()),
        ));
    }

    public function testCompleteRenewalNotInitiated(): void
    {
        $renewalTime = $this->now->add($this->interval->toDateInterval());
        $this->given(
            new RecurringDonationActivated(
                $this->now,
                $this->recurringDonationId,
                $renewalTime,
                $this->interval,
            )
        )->when(fn (RecurringDonation $donation) => $donation->completeRenewal($renewalTime))
        ->expectsException(RecurringDonationRenewalNotInitiatedException::class);
    }
}
