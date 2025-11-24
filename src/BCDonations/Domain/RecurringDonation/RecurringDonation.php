<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Psr\Clock\ClockInterface;

#[Aggregate(name: 'recurring_donation')]
class RecurringDonation extends BasicAggregateRoot
{
    #[Id]
    private RecurringDonationId $id;
    private DonationId $activationDonationId;
    private CampaignId $campaignId;
    private Money $amount;
    private Gateway $gateway;
    private RecurringInterval $interval;
    private RecurringDonationStatus $status;
    private bool $isRenewing = false;
    private ?DateTimeImmutable $nextRenewalTime;
    private Email $donorEmail;


    public static function initiate(
        RecurringDonationId $id,
        DonationId $activationDonationId,
        CampaignId $campaignId,
        Money $amount,
        RecurringInterval $interval,
        Email $donorEmail,
        Gateway $gateway,
        ?PersonName $donorName = null,
        ?NationalIdCode $donorNationalIdCode = null,
    ): self {
        $donation = new self();
        $donation->recordThat(new RecurringDonationInitiated(
            $id,
            $activationDonationId,
            $campaignId,
            $amount,
            $interval,
            $donorEmail,
            $gateway,
            $donorName,
            $donorNationalIdCode,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyRecurringDonationInitiated(RecurringDonationInitiated $event): void
    {
        $this->id = $event->id;
        $this->activationDonationId = $event->activationDonationId;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->campaignId = $event->campaignId;
        $this->amount = $event->amount;
        $this->gateway = $event->gateway;
        $this->donorEmail = $event->donorEmail;
    }

    #[Apply]
    protected function applyRecurringDonationActivated(RecurringDonationActivated $event): void
    {
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyRecurringDonationFailing(RecurringDonationFailing $event): void
    {
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyRecurringDonationFailed(RecurringDonationFailed $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    public function initiateRenewal(): void
    {
        if ($this->status !== RecurringDonationStatus::Active) {
            throw new LogicException('Only active recurring donations can be renewed.');
        }

        if ($this->isRenewing) {
            throw new LogicException('Recurring donation is already in the process of renewing.');
        }

        $this->recordThat(new RecurringDonationRenewalInitiated(
            $this->id,
            $this->activationDonationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorEmail,
        ));
    }

    #[Apply]
    protected function applyRecurringDonationRenewalInitiated(RecurringDonationRenewalInitiated $event): void
    {
        $this->isRenewing = true;
    }

    public function completeRenewal(ClockInterface $clock): void
    {
        if (!$this->isRenewing) {
            throw new LogicException('Can only complete renewal for recurring donations that are renewing.');
        }

        $nextRenewalTime = $clock->now()->add($this->interval->toDateInterval());
        $this->recordThat(new RecurringDonationRenewalCompleted($this->id, $nextRenewalTime));
    }

    #[Apply]
    protected function applyRecurringDonationRenewalCompleted(RecurringDonationRenewalCompleted $event): void
    {
        $this->isRenewing = false;
        $this->nextRenewalTime = $event->nextRenewalTime;
    }

    public function activate(ClockInterface $clock): void
    {
        // Idempotency: if already active, do nothing
        if ($this->status === RecurringDonationStatus::Active) {
            return;
        }

        if ($this->status === RecurringDonationStatus::Pending) {
            $nextRenewalTime = $clock->now()->add($this->interval->toDateInterval());
            $this->recordThat(new RecurringDonationActivated($this->id, $nextRenewalTime));
            return;
        }

        if ($this->status === RecurringDonationStatus::Failing) {
            $nextRenewalTime = $this->nextRenewalTime;

            // If for some reason there is no next renewal time, set it to now + interval
            if ($nextRenewalTime === null) {
                $this->recordThat(new RecurringDonationActivated($this->id, $clock->now()->add($this->interval->toDateInterval())));
                return;
            }

            // If renewal time is still in the future, just reactivate
            if ($nextRenewalTime > $clock->now()) {
                $this->recordThat(new RecurringDonationActivated($this->id, $nextRenewalTime));
                return;
            }

            // If renewal time has passed, ensure next renewal time is in the future
            while ($nextRenewalTime <= $clock->now()) {
                $nextRenewalTime = $nextRenewalTime->add($this->interval->toDateInterval());
            }
            $this->recordThat(new RecurringDonationActivated($this->id, $nextRenewalTime));
            return;
        }
        throw new LogicException('Only pending and failing recurring donations can be activated.');
    }

    public function markFailing(): void
    {
        // Idempotency guard
        if ($this->status === RecurringDonationStatus::Failed) {
            return;
        }
        $this->canTransitionToFailing(true);
        $this->recordThat(new RecurringDonationFailing($this->id));
    }

    public function canTransitionToFailing(bool $throw = false): bool
    {
        return $this->canTransition($this->status, RecurringDonationStatus::Failing, [RecurringDonationStatus::Active], $throw);
    }

    public function markFailed(): void
    {
        // Idempotency guard
        if ($this->status === RecurringDonationStatus::Failed) {
            return;
        }
        $this->canTransitionToFailed(true);
        $this->recordThat(new RecurringDonationFailed($this->id));
    }

    public function canTransitionToFailed(bool $throw = false): bool
    {
        return $this->canTransition($this->status, RecurringDonationStatus::Failed, [RecurringDonationStatus::Pending, RecurringDonationStatus::Active, RecurringDonationStatus::Failing], $throw);
    }

    /**
     * @param array<RecurringDonationStatus> $allowedFrom
     */
    private function canTransition(RecurringDonationStatus $from, RecurringDonationStatus $to, array $allowedFrom, bool $throw = false): bool
    {
        $canTransition = in_array($from, $allowedFrom);
        if ($throw && !$canTransition) {
            throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
        }
        return $canTransition;
    }
}
