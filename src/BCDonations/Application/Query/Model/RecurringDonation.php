<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;

class RecurringDonation
{
    private string $id;
    private string $activationDonationId;
    private int $amount;
    private string $currency;
    private string $interval;
    private RecurringDonationStatus $status;
    private string $donorEmail;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getActivationDonationId(): string
    {
        return $this->activationDonationId;
    }

    public function setActivationDonationId(string $activationDonationId): void
    {
        $this->activationDonationId = $activationDonationId;
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

    public function getStatus(): RecurringDonationStatus
    {
        return $this->status;
    }

    public function setStatus(RecurringDonationStatus $status): void
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

    public function getDonorEmail(): string
    {
        return $this->donorEmail;
    }

    public function setDonorEmail(string $donorEmail): void
    {
        $this->donorEmail = $donorEmail;
    }
}
