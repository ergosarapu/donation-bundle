<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;

class Donation
{
    private string $donationId;
    private string $paymentId;
    private int $amount;
    private string $currency;
    private DonationStatus $status;
    private ?string $recurringPlanId = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function getDonationId(): string
    {
        return $this->donationId;
    }

    public function setDonationId(string $donationId): void
    {
        $this->donationId = $donationId;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): void
    {
        $this->paymentId = $paymentId;
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

    public function getStatus(): DonationStatus
    {
        return $this->status;
    }

    public function setStatus(DonationStatus $status): void
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

    public function getRecurringPlanId(): ?string
    {
        return $this->recurringPlanId;
    }

    public function setRecurringPlanId(?string $recurringPlanId): void
    {
        $this->recurringPlanId = $recurringPlanId;
    }
}
