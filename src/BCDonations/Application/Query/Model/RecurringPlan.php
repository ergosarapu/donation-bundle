<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;

class RecurringPlan
{
    private string $recurringPlanId;
    private string $initialDonationId;
    private int $amount;
    private int $cumulativeReceivedAmount = 0;
    private string $currency;
    private string $interval;
    private RecurringPlanStatus $status;
    private ?string $donorEmail;
    private ?string $paymentMethodId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $nextRenewalTime;
    private ?string $renewalInProgressDonationId;

    public function getRecurringPlanId(): string
    {
        return $this->recurringPlanId;
    }

    public function setRecurringPlanId(string $recurringPlanId): void
    {
        $this->recurringPlanId = $recurringPlanId;
    }

    public function getInitialDonationId(): string
    {
        return $this->initialDonationId;
    }

    public function setInitialDonationId(string $initialDonationId): void
    {
        $this->initialDonationId = $initialDonationId;
    }


    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function setInterval(string $interval): void
    {
        $this->interval = $interval;
    }

    public function getStatus(): RecurringPlanStatus
    {
        return $this->status;
    }

    public function setStatus(RecurringPlanStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDonorEmail(): ?string
    {
        return $this->donorEmail;
    }

    public function setDonorEmail(?string $donorEmail): void
    {
        $this->donorEmail = $donorEmail;
    }

    public function getCumulativeReceivedAmount(): int
    {
        return $this->cumulativeReceivedAmount;
    }

    public function setCumulativeReceivedAmount(int $cumulativeReceivedAmount): void
    {
        $this->cumulativeReceivedAmount = $cumulativeReceivedAmount;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(?string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }
    public function getNextRenewalTime(): ?DateTimeImmutable
    {
        return $this->nextRenewalTime;
    }

    public function setNextRenewalTime(?DateTimeImmutable $nextRenewalTime): void
    {
        $this->nextRenewalTime = $nextRenewalTime;
    }

    public function getRenewalInProgressDonationId(): ?string
    {
        return $this->renewalInProgressDonationId;
    }

    public function setRenewalInProgressDonationId(?string $renewalInProgressDonationId): void
    {
        $this->renewalInProgressDonationId = $renewalInProgressDonationId;
    }
}
