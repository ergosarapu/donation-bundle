<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanAction;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
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
            $recurringPlanAction,
            $donationRequest->donorIdentity,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyInitiated(DonationInitiated $event): void
    {
        $this->id = $event->donationId;
        $this->status = $event->status;
        $this->recurringPlanId = $event->recurringPlanAction?->recurringPlanId;
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

        if ($this->status === DonationStatus::Pending) {
            $this->recordThat(new DonationAccepted($currentTime, $this->id, $acceptedAmount, $this->recurringPlanId));
            return;
        }

        throw new LogicException('Cannot transition from ' . $this->status->value . ' to ' . DonationStatus::Accepted->value . '.');
    }

    public function fail(DateTimeImmutable $currentTime): void
    {
        if ($this->status === DonationStatus::Failed) {
            return;
        }

        if ($this->status === DonationStatus::Pending) {
            $this->recordThat(new DonationFailed($currentTime, $this->id, $this->recurringPlanId));
            return;
        }

        throw new LogicException('Cannot transition from ' . $this->status->value . ' to ' . DonationStatus::Failed->value . '.');
    }
}
