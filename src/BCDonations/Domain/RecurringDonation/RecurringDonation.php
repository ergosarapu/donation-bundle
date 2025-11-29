<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation;

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
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

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
        DateTimeImmutable $currentTime,
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
            $currentTime,
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
        $this->nextRenewalTime = null;
    }

    #[Apply]
    protected function applyRecurringDonationActivated(RecurringDonationActivated $event): void
    {
        $this->id = $event->id;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
        $this->interval = $event->interval;
    }

    #[Apply]
    protected function applyRecurringDonationFailing(RecurringDonationFailing $event): void
    {
        $this->id = $event->id;
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyRecurringDonationFailed(RecurringDonationFailed $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    #[Apply]
    protected function applyRecurringDonationExpired(RecurringDonationExpired $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    #[Apply]
    protected function applyRecurringDonationCanceled(RecurringDonationCanceled $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    public function initiateRenewal(DateTimeImmutable $currentTime): void
    {
        if ($this->isRenewing) {
            throw new RecurringDonationRenewalAlreadyInitiatedException();
        }

        if ($this->nextRenewalTime > $currentTime) {
            throw new RecurringDonationRenewalNotDueYetException();
        }

        $closure = match ($this->status) {
            RecurringDonationStatus::Active => fn () => $this->recordThat(new RecurringDonationRenewalInitiated(
                $currentTime,
                $this->id,
                $this->activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->donorEmail,
            )),
            default => fn () => throw new RecurringDonationRenewalNotAllowedException('Only active recurring donations can be renewed.'),
        };
        $closure->call($this);
    }

    #[Apply]
    protected function applyRecurringDonationRenewalInitiated(RecurringDonationRenewalInitiated $event): void
    {
        $this->isRenewing = true;
    }

    public function completeRenewal(DateTimeImmutable $currentTime): void
    {
        if (!$this->isRenewing) {
            throw new RecurringDonationRenewalNotInitiatedException();
        }
        $this->recordThat(
            new RecurringDonationRenewalCompleted(
                $currentTime,
                $this->id,
                $this->calculateNextRenewalTime($currentTime)
            )
        );
    }

    #[Apply]
    protected function applyRecurringDonationRenewalCompleted(RecurringDonationRenewalCompleted $event): void
    {
        $this->isRenewing = false;
        $this->nextRenewalTime = $event->nextRenewalTime;
    }

    public function activate(DateTimeImmutable $currentTime): void
    {
        $result = match ($this->status) {
            RecurringDonationStatus::Pending => fn () => $this->calculateNextRenewalTimeAndActivate($currentTime),
            RecurringDonationStatus::Failing => fn () => $this->calculateNextRenewalTimeAndActivate($currentTime),
            default => fn () => throw new RecurringDonationActivateNotAllowedException('Activate not allowed from status: ' . $this->status->value),
        };
        $result->call($this);
    }

    private function calculateNextRenewalTimeAndActivate(DateTimeImmutable $now): void
    {
        $this->recordThat(
            new RecurringDonationActivated(
                $now,
                $this->id,
                $this->calculateNextRenewalTime($now),
                $this->interval
            )
        );
    }

    private function calculateNextRenewalTime(DateTimeImmutable $now): DateTimeImmutable
    {
        $nextRenewalTime = $this->nextRenewalTime;
        if ($nextRenewalTime === null) {
            return $now->add($this->interval->toDateInterval());
        }

        // Make sure the next renewal time is in the future
        while ($nextRenewalTime <= $now) {
            $nextRenewalTime = $nextRenewalTime->add($this->interval->toDateInterval());
        }
        return $nextRenewalTime;
    }

    public function markFailing(DateTimeImmutable $currentTime): void
    {
        $result = match ($this->status) {
            RecurringDonationStatus::Active => fn () => $this->recordThat(new RecurringDonationFailing($currentTime, $this->id)),
            default => fn () => throw new RecurringDonationMarkFailingNotAllowedException('Mark Failing not allowed from status: ' . $this->status->value),
        };
        $result->call($this);
    }

    public function markFailed(DateTimeImmutable $currentTime): void
    {
        $closure = match ($this->status) {
            RecurringDonationStatus::Failing => fn () => $this->recordThat(new RecurringDonationFailed($currentTime, $this->id)),
            RecurringDonationStatus::Pending => fn () => $this->recordThat(new RecurringDonationFailed($currentTime, $this->id)),
            RecurringDonationStatus::Active => fn () => $this->recordThat(new RecurringDonationFailed($currentTime, $this->id)),
            default => fn () => throw new RecurringDonationMarkFailedNotAllowedException('Mark Failed not allowed from status: ' . $this->status->value),
        };
        $closure->call($this);
    }

    public function markCanceled(DateTimeImmutable $currentTime): void
    {
        $closure = match ($this->status) {
            RecurringDonationStatus::Failing => fn () => $this->recordThat(new RecurringDonationCanceled($currentTime, $this->id)),
            RecurringDonationStatus::Pending => fn () => $this->recordThat(new RecurringDonationCanceled($currentTime, $this->id)),
            RecurringDonationStatus::Active => fn () => $this->recordThat(new RecurringDonationCanceled($currentTime, $this->id)),
            default => fn () => throw new RecurringDonationMarkCanceledNotAllowedException('Mark Canceled not allowed from status: ' . $this->status->value),
        };
        $closure->call($this);
    }

}
