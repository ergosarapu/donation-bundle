<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

use DateTimeImmutable;

class Claim
{
    private string $claimId;
    private ?string $paymentId = null;
    private ?string $donationId = null;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $rawName = null;
    private ?string $email = null;
    private ?string $iban = null;
    private ?string $legalIdentifier = null;
    private bool $inReview = false;
    private bool $resolved = false;
    private ?string $reviewReason = null;
    private ?string $identityId = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function getClaimId(): string
    {
        return $this->claimId;
    }

    public function setClaimId(string $claimId): void
    {
        $this->claimId = $claimId;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): void
    {
        $this->paymentId = $paymentId;
    }

    public function getDonationId(): ?string
    {
        return $this->donationId;
    }

    public function setDonationId(?string $donationId): void
    {
        $this->donationId = $donationId;
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

    public function getRawName(): ?string
    {
        return $this->rawName;
    }

    public function setRawName(?string $rawName): void
    {
        $this->rawName = $rawName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    public function getLegalIdentifier(): ?string
    {
        return $this->legalIdentifier;
    }

    public function setLegalIdentifier(?string $legalIdentifier): void
    {
        $this->legalIdentifier = $legalIdentifier;
    }

    public function isInReview(): bool
    {
        return $this->inReview;
    }

    public function setInReview(bool $inReview): void
    {
        $this->inReview = $inReview;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): void
    {
        $this->resolved = $resolved;
    }

    public function getReviewReason(): ?string
    {
        return $this->reviewReason;
    }

    public function setReviewReason(?string $reviewReason): void
    {
        $this->reviewReason = $reviewReason;
    }

    public function getIdentityId(): ?string
    {
        return $this->identityId;
    }

    public function setIdentityId(?string $identityId): void
    {
        $this->identityId = $identityId;
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
}
