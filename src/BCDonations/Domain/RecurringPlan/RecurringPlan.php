<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

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
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
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
    private ?DonorDetails $donorDetails;
    private ?PaymentMethodId $paymentMethodId;
    private ShortDescription $description;

    public static function initiate(
        DateTimeImmutable $currentTime,
        RecurringPlanId $recurringPlanId,
        RecurringPlanAction $recurringPlanAction,
        DonationRequest $initialDonationRequest,
        RecurringInterval $interval,
    ): self {
        if ($initialDonationRequest->donorDetails->email === null) {
            throw new InvalidArgumentException('Recurring plan requires donor email');
        }

        $donation = new self();
        $donation->recordThat(new RecurringPlanInitiated(
            $currentTime,
            $recurringPlanId,
            $recurringPlanAction,
            $initialDonationRequest->donationId,
            $initialDonationRequest->campaignId,
            $initialDonationRequest->amount,
            $interval,
            $initialDonationRequest->gateway,
            $initialDonationRequest->donorDetails,
            $initialDonationRequest->description,
        ));
        return $donation;
    }

    public static function create(
        DateTimeImmutable $currentTime,
        RecurringPlanId $recurringPlanId,
        RecurringPlanStatus $status,
        RecurringInterval $interval,
        DonationId $initialDonationId,
        CampaignId $campaignId,
        PaymentMethodId $paymentMethodId,
        Money $amount,
        Gateway $gateway,
        DonorDetails $donorDetails,
        ?DateTimeImmutable $nextRenewalTime,
        ShortDescription $description,
        DateTimeImmutable $initiatedAt,
    ): self {
        if ($donorDetails->email === null) {
            throw new InvalidArgumentException('Recurring plan requires donor email');
        }
        $donation = new self();
        $donation->recordThat(new RecurringPlanCreated(
            $currentTime,
            $initiatedAt,
            $recurringPlanId,
            $status,
            $interval,
            $initialDonationId,
            $campaignId,
            $paymentMethodId,
            $amount,
            $gateway,
            $donorDetails,
            $description,
            $nextRenewalTime,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyCreated(RecurringPlanCreated $event): void
    {
        $this->id = $event->recurringPlanId;
        $this->renwalDonationInProgress = null;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->campaignId = $event->campaignId;
        $this->amount = $event->amount;
        $this->gateway = $event->gateway;
        $this->donorDetails = $event->donorDetails;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->paymentMethodId = $event->paymentMethodId;
        $this->description = $event->description;
    }

    #[Apply]
    protected function applyInitiated(RecurringPlanInitiated $event): void
    {
        $this->id = $event->recurringPlanId;
        $this->renwalDonationInProgress = null;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->campaignId = $event->campaignId;
        $this->amount = $event->amount;
        $this->gateway = $event->gateway;
        $this->donorDetails = $event->donorDetails;
        $this->nextRenewalTime = null;
        $this->paymentMethodId = null;
        $this->description = $event->description;
    }

    #[Apply]
    protected function applyActivated(RecurringPlanActivated $event): void
    {
        $this->id = $event->id;
        $this->nextRenewalTime = $event->nextRenewalTime;
        $this->status = $event->status;
        $this->interval = $event->interval;
        $this->renwalDonationInProgress = null;
        $this->paymentMethodId = $event->paymentMethodId;
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

        if ($this->donorDetails === null) {
            throw new LogicException('Missing donor details. Personal info may have been deleted.');
        }

        match ($this->status) {
            RecurringPlanStatus::Active, RecurringPlanStatus::Failing => null,
            default => throw new RecurringPlanRenewalNotAllowedException('Only active and failing recurring plans can be renewed.'),
        };

        if ($this->paymentMethodId === null) {
            throw new LogicException('Cannot initiate renewal: payment method not assigned.');
        }

        $renewalAction = RecurringPlanAction::forRenew($this->paymentMethodId);
        $this->recordThat(new RecurringPlanRenewalInitiated(
            $currentTime,
            $this->id,
            $renewalAction,
            $renewalDonationId,
            $this->campaignId,
            $this->amount,
            $this->gateway,
            $this->donorDetails,
            $this->description,
        ));
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
            // Note that we may get here if the same donation was already processed due to retries,
            // in this case it is safe to ignore the call
            return;
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

    public function activate(DateTimeImmutable $currentTime, PaymentMethodId $paymentMethodId): void
    {
        match ($this->status) {
            RecurringPlanStatus::Initiated => $this->calculateNextRenewalTimeAndActivate($currentTime, $paymentMethodId),
            default => throw new RecurringPlanActivateNotAllowedException('Activate not allowed from status: ' . $this->status->value),
        };
    }

    public function reActivate(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Failing => null,
            default => throw new RecurringPlanReActivateNotAllowedException('Re-activate not allowed from status: ' . $this->status->value),
        };
        if ($this->paymentMethodId === null) {
            throw new LogicException('Cannot reactivate recurring plan: payment method not assigned.');
        }
        $this->calculateNextRenewalTimeAndActivate($currentTime, $this->paymentMethodId);
    }

    private function calculateNextRenewalTimeAndActivate(DateTimeImmutable $now, PaymentMethodId $paymentMethodId): void
    {
        $this->recordThat(
            new RecurringPlanActivated(
                $now,
                $this->id,
                $this->calculateNextRenewalTime($now),
                $this->interval,
                $paymentMethodId,
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
            RecurringPlanStatus::Initiated,
            RecurringPlanStatus::Active => $this->recordThat(new RecurringPlanCanceled($currentTime, $this->id)),
            default => throw new RecurringPlanCancelNotAllowedException('Cancelling not allowed from status: ' . $this->status->value),
        };
    }

    public function fail(DateTimeImmutable $currentTime): void
    {
        match ($this->status) {
            RecurringPlanStatus::Failing,
            RecurringPlanStatus::Initiated,
            RecurringPlanStatus::Active => $this->recordThat(new RecurringPlanFailed($currentTime, $this->id)),
            default => throw new RecurringPlanFailNotAllowedException('Fail not allowed from status: ' . $this->status->value),
        };
    }
}
