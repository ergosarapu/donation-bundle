<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
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
    private Money $amount;
    private DonationStatus $status;
    private bool $recurringActivation = false;
    private ?RecurringPlanId $recurringPlanId = null;

    public static function initiate(
        DateTimeImmutable $currentTime,
        DonationId $id,
        CampaignId $campaignId,
        PaymentId $paymentId,
        Money $amount,
        Gateway $gateway,
        bool $recurringActivation,
        ?RecurringPlanId $recurringPlanId = null,
        ?PersonName $donorName = null,
        ?Email $donorEmail = null,
        ?NationalIdCode $donorNationalIdCode = null,
        ?DonationId $parentRecurringActivationDonationId = null,
    ): self {
        $donation = new self();
        $donation->recordThat(new DonationInitiated(
            $currentTime,
            $id,
            $amount,
            DonationStatus::Pending,
            $campaignId,
            $paymentId,
            $gateway,
            new ShortDescription('TODO: Add description'),
            $recurringActivation,
            $recurringPlanId,
            $donorName,
            $donorEmail,
            $donorNationalIdCode,
            $parentRecurringActivationDonationId,
        ));
        return $donation;
    }

    #[Apply]
    protected function applyInitiated(DonationInitiated $event): void
    {
        $this->id = $event->donationId;
        $this->status = $event->status;
        $this->amount = $event->amount;
        $this->recurringPlanId = $event->recurringPlanId;
        $this->recurringActivation = $event->recurringActivation;
    }

    #[Apply]
    protected function applyAccepted(DonationAccepted $event): void
    {
        $this->status = $event->status;
        $this->amount = $event->acceptedAmount;
    }

    #[Apply]
    protected function applyFailed(DonationFailed $event): void
    {
        $this->status = $event->status;
    }

    public function markAccepted(DateTimeImmutable $currentTime, Money $acceptedAmount): void
    {
        // Idempotency guard
        if ($this->status === DonationStatus::Accepted) {
            if (!$this->amount->equals($acceptedAmount)) {
                throw new LogicException('Donation already Accepted with different amount, existing: ' . $this->amount . ', new: ' . $acceptedAmount);
            }
            return;
        }
        $this->canTransitionToAccepted(true);
        $this->recordThat(new DonationAccepted($currentTime, $this->id, $acceptedAmount, $this->recurringActivation, $this->recurringPlanId));
    }

    public function canTransitionToAccepted(bool $throw = false): bool
    {
        return $this->canTransition($this->status, DonationStatus::Accepted, [DonationStatus::Pending], $throw);
    }

    public function markFailed(DateTimeImmutable $currentTime): void
    {
        // Idempotency guard
        if ($this->status === DonationStatus::Failed) {
            return;
        }
        $this->canTransitionToFailed(true);
        $this->recordThat(new DonationFailed($currentTime, $this->id, $this->recurringActivation, $this->recurringPlanId));
    }

    public function canTransitionToFailed(bool $throw = false): bool
    {
        return $this->canTransition($this->status, DonationStatus::Failed, [DonationStatus::Pending], $throw);
    }

    /**
     * @param array<DonationStatus> $allowedFrom
     */
    private function canTransition(DonationStatus $from, DonationStatus $to, array $allowedFrom, bool $throw = false): bool
    {
        $canTransition = in_array($from, $allowedFrom);
        if ($throw && !$canTransition) {
            throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
        }
        return $canTransition;
    }
}
