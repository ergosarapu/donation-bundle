<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Aggregate;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Event\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkCanceledNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkFailedNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanMarkFailingNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalAlreadyInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Exception\RecurringPlanRenewalNotInitiatedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\ValueObject\RecurringInterval;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'recurring_plan')]
class RecurringPlan extends BasicAggregateRoot
{
    #[Id]
    private RecurringPlanId $id;
    private DonationId $activationDonationId;
    private CampaignId $campaignId;
    private Money $amount;
    private Gateway $gateway;
    private RecurringInterval $interval;
    private RecurringPlanStatus $status;
    private bool $isRenewing = false;
    private ?DateTimeImmutable $nextRenewalTime;
    private Email $donorEmail;


    public static function initiate(
        DateTimeImmutable $currentTime,
        RecurringPlanId $id,
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
        $donation->recordThat(new RecurringPlanInitiated(
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
    protected function applyInitiated(RecurringPlanInitiated $event): void
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
    protected function applyActivated(RecurringPlanActivated $event): void
    {
        $this->id = $event->id;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
        $this->interval = $event->interval;
    }

    #[Apply]
    protected function applyFailing(RecurringPlanFailing $event): void
    {
        $this->id = $event->id;
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyFailed(RecurringPlanFailed $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    #[Apply]
    protected function applyExpired(RecurringPlanExpired $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    #[Apply]
    protected function applyCanceled(RecurringPlanCanceled $event): void
    {
        $this->status = $event->status;
        $this->nextRenewalTime = null;
    }

    public function initiateRenewal(DateTimeImmutable $currentTime): void
    {
        if ($this->isRenewing) {
            throw new RecurringPlanRenewalAlreadyInitiatedException();
        }

        if ($this->nextRenewalTime > $currentTime) {
            throw new RecurringPlanRenewalNotDueYetException();
        }

        $closure = match ($this->status) {
            RecurringPlanStatus::Active => fn () => $this->recordThat(new RecurringPlanRenewalInitiated(
                $currentTime,
                $this->id,
                $this->activationDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->donorEmail,
            )),
            default => fn () => throw new RecurringPlanRenewalNotAllowedException('Only active recurring donations can be renewed.'),
        };
        $closure->call($this);
    }

    #[Apply]
    protected function applyRenewalInitiated(RecurringPlanRenewalInitiated $event): void
    {
        $this->isRenewing = true;
    }

    public function completeRenewal(DateTimeImmutable $currentTime): void
    {
        if (!$this->isRenewing) {
            throw new RecurringPlanRenewalNotInitiatedException();
        }
        $this->recordThat(
            new RecurringPlanRenewalCompleted(
                $currentTime,
                $this->id,
                $this->calculateNextRenewalTime($currentTime)
            )
        );
    }

    #[Apply]
    protected function applyRenewalCompleted(RecurringPlanRenewalCompleted $event): void
    {
        $this->isRenewing = false;
        $this->nextRenewalTime = $event->nextRenewalTime;
    }

    public function activate(DateTimeImmutable $currentTime): void
    {
        $result = match ($this->status) {
            RecurringPlanStatus::Pending => fn () => $this->calculateNextRenewalTimeAndActivate($currentTime),
            RecurringPlanStatus::Failing => fn () => $this->calculateNextRenewalTimeAndActivate($currentTime),
            default => fn () => throw new RecurringPlanActivateNotAllowedException('Activate not allowed from status: ' . $this->status->value),
        };
        $result->call($this);
    }

    private function calculateNextRenewalTimeAndActivate(DateTimeImmutable $now): void
    {
        $this->recordThat(
            new RecurringPlanActivated(
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
            RecurringPlanStatus::Active => fn () => $this->recordThat(new RecurringPlanFailing($currentTime, $this->id)),
            default => fn () => throw new RecurringPlanMarkFailingNotAllowedException('Mark Failing not allowed from status: ' . $this->status->value),
        };
        $result->call($this);
    }

    public function markFailed(DateTimeImmutable $currentTime): void
    {
        $closure = match ($this->status) {
            RecurringPlanStatus::Failing => fn () => $this->recordThat(new RecurringPlanFailed($currentTime, $this->id)),
            RecurringPlanStatus::Pending => fn () => $this->recordThat(new RecurringPlanFailed($currentTime, $this->id)),
            RecurringPlanStatus::Active => fn () => $this->recordThat(new RecurringPlanFailed($currentTime, $this->id)),
            default => fn () => throw new RecurringPlanMarkFailedNotAllowedException('Mark Failed not allowed from status: ' . $this->status->value),
        };
        $closure->call($this);
    }

    public function markCanceled(DateTimeImmutable $currentTime): void
    {
        $closure = match ($this->status) {
            RecurringPlanStatus::Failing => fn () => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            RecurringPlanStatus::Pending => fn () => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            RecurringPlanStatus::Active => fn () => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            default => fn () => throw new RecurringPlanMarkCanceledNotAllowedException('Mark Canceled not allowed from status: ' . $this->status->value),
        };
        $closure->call($this);
    }

}
