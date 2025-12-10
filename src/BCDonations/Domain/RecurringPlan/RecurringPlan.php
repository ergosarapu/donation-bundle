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
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanMarkCanceledNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotAllowedException;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception\RecurringPlanRenewalNotDueYetException;
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
    private ?DonationId $donationInProgress;
    private CampaignId $campaignId;
    private Money $amount;
    private Gateway $gateway;
    private RecurringInterval $interval;
    private RecurringPlanStatus $status;
    private ?DateTimeImmutable $nextRenewalTime;
    private DonorIdentity $donorIdentity;
    private ?RecurringToken $recurringToken;

    public static function initiate(
        DateTimeImmutable $currentTime,
        RecurringPlanId $id,
        DonationRequest $activationDonationRequest,
        RecurringInterval $interval,
    ): self {
        if ($activationDonationRequest->donorIdentity->email === null) {
            throw new InvalidArgumentException('Recurring plan requires donor email');
        }

        $donation = new self();
        $donation->recordThat(new RecurringPlanInitiated(
            $currentTime,
            $id,
            $activationDonationRequest->donationId,
            $activationDonationRequest->campaignId,
            $activationDonationRequest->amount,
            $interval,
            $activationDonationRequest->gateway,
            $activationDonationRequest->donorIdentity,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyInitiated(RecurringPlanInitiated $event): void
    {
        $this->id = $event->id;
        $this->donationInProgress = $event->activationDonationId;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->campaignId = $event->campaignId;
        $this->amount = $event->amount;
        $this->gateway = $event->gateway;
        $this->donorIdentity = $event->donorIdentity;
        $this->nextRenewalTime = null;
        $this->recurringToken = null;
    }

    #[Apply]
    protected function applyActivated(RecurringPlanActivated $event): void
    {
        $this->id = $event->id;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->donationInProgress = null;
        $this->recurringToken = $event->recurringToken;
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
        if ($this->donationInProgress !== null) {
            throw new LogicException('Donation is in progress.');
        }

        if ($this->recurringToken === null) {
            throw new RecurringPlanRenewalNotAllowedException('Missing recurring token.');
        }

        if ($this->nextRenewalTime > $currentTime) {
            throw new RecurringPlanRenewalNotDueYetException();
        }

        match ($this->status) {
            RecurringPlanStatus::Active, RecurringPlanStatus::Failing => $this->recordThat(new RecurringPlanRenewalInitiated(
                $currentTime,
                $this->id,
                $renewalDonationId,
                $this->campaignId,
                $this->amount,
                $this->gateway,
                $this->donorIdentity,
                $this->recurringToken,
            )),
            default =>  throw new RecurringPlanRenewalNotAllowedException('Only active and failing recurring donations can be renewed.'),
        };
    }

    #[Apply]
    protected function applyRenewalInitiated(RecurringPlanRenewalInitiated $event): void
    {
        $this->donationInProgress = $event->renewalDonationId;
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
        $this->donationInProgress = null;
        $this->nextRenewalTime = $event->nextRenewalTime;
    }

    public function completeRecurringAttempt(
        DateTimeImmutable $currentTime,
        DonationId $donationId,
        DonationStatus $donationStatus,
        ?RecurringToken $recurringToken,
        bool $temporalFailure = false,
    ): void {

        // Validate donation is in progress
        if ($donationId->toString() !== $this->donationInProgress?->toString()) {
            // Note that we may get here if the same donation was already processed due to retries, in this case it is safe to ignore the exception
            throw new InvalidArgumentException('Donation id ' . $donationId->toString() . ' does not match in progress id: ' . $this->donationInProgress?->toString());
        }

        match($donationStatus) {
            DonationStatus::Accepted => $this->completeSuccessfulRecurringAttempt($currentTime, $recurringToken),
            DonationStatus::Failed => $this->completeUnsuccessfulRecurringAttempt($currentTime, $temporalFailure),
            default => throw new InvalidArgumentException('Unsupported donation status: ' . $donationStatus->value),
        };
    }

    private function completeSuccessfulRecurringAttempt(DateTimeImmutable $currentTime, ?RecurringToken $recurringToken): void
    {
        if ($this->status === RecurringPlanStatus::Pending) {
            if ($recurringToken === null) {
                // $this->markFailed($currentTime);
                $this->recordThat(new RecurringPlanFailed($currentTime, $this->id));
            } else {
                $this->activate($currentTime, $recurringToken);
            }
            return;
        }
        if ($this->status === RecurringPlanStatus::Active) {
            $this->completeRenewal($currentTime);
            return;
        }
        if ($this->status === RecurringPlanStatus::Failing) {
            $this->completeRenewal($currentTime);
            $this->activate($currentTime, $this->recurringToken);
            return;
        }
    }

    private function completeUnsuccessfulRecurringAttempt(DateTimeImmutable $currentTime, bool $temporalFailure): void
    {
        if ($temporalFailure) {
            // $this->markFailing($currentTime);
            $this->recordThat(new RecurringPlanFailing($currentTime, $this->id));
        } else {
            $this->recordThat(new RecurringPlanFailed($currentTime, $this->id));
            // $this->markFailed($currentTime);
        }
    }

    public function activate(DateTimeImmutable $currentTime, ?RecurringToken $recurringToken = null): void
    {
        match ($this->status) {
            RecurringPlanStatus::Pending => $this->calculateNextRenewalTimeAndActivate($currentTime, $recurringToken),
            RecurringPlanStatus::Failing => $this->calculateNextRenewalTimeAndActivate($currentTime, $recurringToken),
            default => throw new RecurringPlanActivateNotAllowedException('Activate not allowed from status: ' . $this->status->value),
        };
    }

    private function calculateNextRenewalTimeAndActivate(DateTimeImmutable $now, ?RecurringToken $recurringToken): void
    {
        $this->recordThat(
            new RecurringPlanActivated(
                $now,
                $this->id,
                $this->calculateNextRenewalTime($now),
                $this->interval,
                $recurringToken,
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
            RecurringPlanStatus::Failing => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            RecurringPlanStatus::Pending => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            RecurringPlanStatus::Active => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            default => throw new RecurringPlanMarkCanceledNotAllowedException('Mark Canceled not allowed from status: ' . $this->status->value),
        };
    }

}
