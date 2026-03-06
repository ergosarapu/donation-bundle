<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'donation')]
class Donation extends BasicAggregateRoot
{
    #[Id]
    private DonationId $id;
    private DonationStatus $status;
    private ?RecurringPlanId $recurringPlanId = null;

    public static function initiate(
        DateTimeImmutable $currentTime,
        DonationRequest $donationRequest,
        ?RecurringPlanId $recurringPlanId = null,
        ?RecurringPlanAction $recurringPlanAction = null,
    ): self {

        $donation = new self();
        $donation->recordThat(new DonationInitiated(
            $currentTime,
            $donationRequest->donationId,
            $donationRequest->amount,
            $donationRequest->campaignId,
            $donationRequest->paymentId,
            $donationRequest->gateway,
            $donationRequest->description,
            $recurringPlanId,
            $recurringPlanAction,
            $donationRequest->donorDetails,
        ));
        return $donation;
    }

    public static function create(
        DateTimeImmutable $currentTime,
        DonationId $donationId,
        Money $amount,
        CampaignId $campaignId,
        PaymentId $paymentId,
        ShortDescription $description,
        DonorDetails $donorDetails,
        ?RecurringPlanId $recurringPlanId,
        ?DateTimeImmutable $createdAt
    ): self {
        $donation = new self();
        $donation->recordThat(new DonationCreated(
            $currentTime,
            $donationId,
            $amount,
            $campaignId,
            $paymentId,
            $description,
            $donorDetails,
            $recurringPlanId,
            $createdAt ?? $currentTime,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyInitiated(DonationInitiated $event): void
    {
        $this->id = $event->donationId;
        $this->status = $event->status;
        $this->recurringPlanId = $event->recurringPlanId;
    }

    #[Apply]
    protected function applyCreated(DonationCreated $event): void
    {
        $this->id = $event->donationId;
        $this->status = $event->status;
        $this->recurringPlanId = $event->recurringPlanId;
    }

    #[Apply]
    protected function applyAccepted(DonationAccepted $event): void
    {
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyFailed(DonationFailed $event): void
    {
        $this->status = $event->status;
    }

    public function accept(DateTimeImmutable $currentTime, Money $acceptedAmount): void
    {
        if ($this->status === DonationStatus::Accepted) {
            return;
        }

        $this->validateTransitionToAccepted();
        $this->recordThat(new DonationAccepted($currentTime, $this->id, $acceptedAmount, $this->recurringPlanId));
    }

    public function validateTransitionToAccepted(): void
    {
        if ($this->status === DonationStatus::Pending) {
            return;
        }
        if ($this->status === DonationStatus::Created) {
            return;
        }
        $this->failTransitionValidation($this->status, DonationStatus::Accepted);
    }

    private function failTransitionValidation(DonationStatus $from, DonationStatus $to): void
    {
        throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
    }

    public function fail(DateTimeImmutable $currentTime): void
    {
        if ($this->status === DonationStatus::Failed) {
            return;
        }

        $this->validateTransitionToFailed();
        $this->recordThat(new DonationFailed($currentTime, $this->id, $this->recurringPlanId));
    }

    public function validateTransitionToFailed(): void
    {
        if ($this->status === DonationStatus::Pending) {
            return;
        }
        if ($this->status === DonationStatus::Created) {
            return;
        }
        $this->failTransitionValidation($this->status, DonationStatus::Failed);
    }
}
