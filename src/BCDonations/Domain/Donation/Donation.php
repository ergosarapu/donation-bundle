<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Donation;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
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
    private ?RecurringDonationId $recurringDonationId = null;

    public static function initiate(
        DonationId $id,
        CampaignId $campaignId,
        PaymentId $paymentId,
        Money $amount,
        Gateway $gateway,
        bool $recurringActivation,
        ?RecurringDonationId $recurringDonationId = null,
        ?PersonName $donorName = null,
        ?Email $donorEmail = null,
        ?NationalIdCode $donorNationalIdCode = null,
        ?DonationId $parentRecurringActivationDonationId = null,
    ): self {
        $donation = new self();
        $donation->recordThat(new DonationInitiated(
            $id,
            $amount,
            DonationStatus::Pending,
            $campaignId,
            $paymentId,
            $gateway,
            new ShortDescription('TODO: Add description'),
            $recurringActivation,
            $recurringDonationId,
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
        $this->recurringDonationId = $event->recurringDonationId;
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

    public function markAccepted(Money $acceptedAmount): void
    {
        // Idempotency guard
        if ($this->status === DonationStatus::Accepted) {
            if (!$this->amount->equals($acceptedAmount)) {
                throw new LogicException('Donation already Accepted with different amount, existing: ' . $this->amount . ', new: ' . $acceptedAmount);
            }
            return;
        }
        $this->canTransitionToAccepted(true);
        $this->recordThat(new DonationAccepted($this->id, $acceptedAmount, $this->recurringActivation, $this->recurringDonationId));
    }

    public function canTransitionToAccepted(bool $throw = false): bool
    {
        return $this->canTransition($this->status, DonationStatus::Accepted, [DonationStatus::Pending], $throw);
    }

    public function markFailed(): void
    {
        // Idempotency guard
        if ($this->status === DonationStatus::Failed) {
            return;
        }
        $this->canTransitionToFailed(true);
        $this->recordThat(new DonationFailed($this->id, $this->recurringActivation, $this->recurringDonationId));
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
