<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanCancelNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanFailNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanReActivateNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotDueYetException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use InvalidArgumentException;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'recurring_plan')]
class RecurringPlan extends BasicAggregateRoot
{
    #[Id]
    private RecurringPlanId $id;
    private ?DonationId $renwalDonationInProgress;
    private CampaignId $campaignId;
    private Money $amount;
    private Gateway $gateway;
    private RecurringInterval $interval;
    private RecurringPlanStatus $status;
    private ?DateTimeImmutable $nextRenewalTime;
    private DonorIdentity $donorIdentity;
    private PaymentMethodId $paymentMethodId;

    public static function initiate(
        DateTimeImmutable $currentTime,
        RecurringPlanAction $recurringPlanAction,
        DonationRequest $initialDonationRequest,
        RecurringInterval $interval,
    ): self {
        if ($initialDonationRequest->donorIdentity->email === null) {
            throw new InvalidArgumentException('Recurring plan requires donor email');
        }

        $donation = new self();
        $donation->recordThat(new RecurringPlanInitiated(
            $currentTime,
            $recurringPlanAction,
            $initialDonationRequest->donationId,
            $initialDonationRequest->campaignId,
            $initialDonationRequest->amount,
            $interval,
            $initialDonationRequest->gateway,
            $initialDonationRequest->donorIdentity,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyInitiated(RecurringPlanInitiated $event): void
    {
        $this->id = $event->recurringPlanAction->recurringPlanId;
        $this->renwalDonationInProgress = null;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->campaignId = $event->campaignId;
        $this->amount = $event->amount;
        $this->gateway = $event->gateway;
        $this->donorIdentity = $event->donorIdentity;
        $this->nextRenewalTime = null;
        $this->paymentMethodId = $event->recurringPlanAction->paymentMethodId;
    }

    #[Apply]
    protected function applyActivated(RecurringPlanActivated $event): void
    {
        $this->id = $event->id;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->renwalDonationInProgress = null;
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

    public function initiateRenewal(DateTimeImmutable $currentTime, DonationId $renewalDonationId): void
    {
        if ($this->renwalDonationInProgress !== null) {
            throw new LogicException('Renewal Donation is in progress.');
        }

        if ($this->nextRenewalTime > $currentTime) {
            throw new RecurringPlanRenewalNotDueYetException();
        }

        match ($this->status) {
            RecurringPlanStatus::Active, RecurringPlanStatus::Failing => $this->recordThat(new RecurringPlanRenewalInitiated(
                $currentTime,
                RecurringPlanAction::forRenew($this->id, $this->paymentMethodId),
                $renewalDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->donorIdentity,
            )),
            default =>  throw new RecurringPlanRenewalNotAllowedException('Only active and failing recurring donations can be renewed.'),
        };
    }

    #[Apply]
    protected function applyRenewalInitiated(RecurringPlanRenewalInitiated $event): void
    {
        $this->renwalDonationInProgress = $event->renewalDonationId;
    }

    private function completeRenewal(DateTimeImmutable $currentTime): void
    {
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
        $this->renwalDonationInProgress = null;
        $this->nextRenewalTime = $event->nextRenewalTime;
    }

    public function completeRecurringAttempt(
        DateTimeImmutable $currentTime,
        DonationId $donationId,
        DonationStatus $donationStatus,
    ): void {

        // Validate donation is in progress
        if ($donationId->toString() !== $this->renwalDonationInProgress?->toString()) {
            // Note that we may get here if the same donation was already processed due to retries, in this case it is safe to ignore the exception
            throw new InvalidArgumentException('Donation id ' . $donationId->toString() . ' does not match in progress id: ' . $this->renwalDonationInProgress?->toString());
        }

        match($donationStatus) {
            DonationStatus::Accepted => $this->completeSuccessfulRecurringAttempt($currentTime),
            DonationStatus::Failed => $this->completeUnsuccessfulRecurringAttempt($currentTime),
            default => throw new InvalidArgumentException('Unsupported donation status: ' . $donationStatus->value),
        };
    }

    private function completeSuccessfulRecurringAttempt(DateTimeImmutable $currentTime): void
    {
        if ($this->status === RecurringPlanStatus::Active) {
            $this->completeRenewal($currentTime);
            return;
        }
        if ($this->status === RecurringPlanStatus::Failing) {
            $this->completeRenewal($currentTime);
            $this->reActivate($currentTime);
            return;
        }
    }

    private function completeUnsuccessfulRecurringAttempt(DateTimeImmutable $currentTime): void
    {
        if ($this->status === RecurringPlanStatus::Failed) {
            return;
        }
        if ($this->status === RecurringPlanStatus::Expired) {
            return;
        }
        if ($this->status === RecurringPlanStatus::Canceled) {
            return;
        }
        $this->recordThat(new RecurringPlanFailing($currentTime, $this->id));
    }

    public function activate(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Pending => $this->calculateNextRenewalTimeAndActivate($currentTime),
            default => throw new RecurringPlanActivateNotAllowedException('Activate not allowed from status: ' . $this->status->value),
        };
    }

    public function reActivate(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Failing => $this->calculateNextRenewalTimeAndActivate($currentTime),
            default => throw new RecurringPlanReActivateNotAllowedException('Re-activate not allowed from status: ' . $this->status->value),
        };
    }

    private function calculateNextRenewalTimeAndActivate(DateTimeImmutable $now): void
    {
        $this->recordThat(
            new RecurringPlanActivated(
                $now,
                $this->id,
                $this->calculateNextRenewalTime($now),
                $this->interval,
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


    public function cancel(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Failing,
            RecurringPlanStatus::Pending,
            RecurringPlanStatus::Active => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            default => throw new RecurringPlanCancelNotAllowedException('Cancelling not allowed from status: ' . $this->status->value),
        };
    }

    public function fail(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Failing,
            RecurringPlanStatus::Pending,
            RecurringPlanStatus::Active => $this->recordThat(new RecurringPlanFailed($currentTime, $this->id)),
            default => throw new RecurringPlanFailNotAllowedException('Failing not allowed from status: ' . $this->status->value),
        };
    }
}
