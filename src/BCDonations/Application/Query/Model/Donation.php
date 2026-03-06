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
    private string $campaignId;
    private ?string $email = null;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $nationalIdCode = null;
    private ?DateTimeImmutable $initiatedAt = null;
    private ?DateTimeImmutable $acceptedAt = null;
    private DateTimeImmutable $effectiveDate;
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
        $this->setEffectiveDate();
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

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): void
    {
        $this->familyName = $familyName;
    }

    public function getNationalIdCode(): ?string
    {
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode): void
    {
        $this->nationalIdCode = $nationalIdCode;
    }

    public function getInitiatedAt(): ?DateTimeImmutable
    {
        return $this->initiatedAt;
    }

    public function setInitiatedAt(?DateTimeImmutable $initiatedAt): void
    {
        $this->initiatedAt = $initiatedAt;
        $this->setEffectiveDate();
    }

    public function getAcceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?DateTimeImmutable $acceptedAt): void
    {
        $this->acceptedAt = $acceptedAt;
        $this->setEffectiveDate();
    }

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    private function setEffectiveDate(): void
    {
        $this->effectiveDate = $this->acceptedAt ?? $this->initiatedAt ?? $this->createdAt;
    }
}
