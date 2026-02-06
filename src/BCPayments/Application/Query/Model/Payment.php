<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;

class Payment
{
    private string $id;
    private int $amount;
    private string $currency;
    private PaymentStatus $status;
    private ?string $gateway = null;
    private ?string $redirectUrl;
    private ?PaymentImportStatus $importStatus = null;
    private ?string $description = null;
    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $accountHolderName = null;
    private ?string $nationalIdCode = null;
    private ?string $organizationRegCode = null;
    private ?string $reference = null;
    private ?string $iban = null;
    private ?string $bic = null;
    private ?string $sourceIdentifier = null;
    private ?string $bankReference = null;
    private ?string $processorReference = null;
    private ?string $legacyPaymentId = null;
    private ?DateTimeImmutable $bookingDate = null;
    private ?DateTimeImmutable $initiatedAt = null;
    private ?DateTimeImmutable $capturedAt = null;
    private ?DateTimeImmutable $authorizedAt = null;
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

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function setStatus(PaymentStatus $status): void
    {
        $this->status = $status;
    }

    public function getGateway(): ?string
    {
        return $this->gateway;
    }

    public function setGateway(?string $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(?string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
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

    public function getImportStatus(): ?PaymentImportStatus
    {
        return $this->importStatus;
    }

    public function setImportStatus(?PaymentImportStatus $importStatus): void
    {
        $this->importStatus = $importStatus;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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

    public function getAccountHolderName(): ?string
    {
        return $this->accountHolderName;
    }

    public function setAccountHolderName(?string $accountHolderName): void
    {
        $this->accountHolderName = $accountHolderName;
    }

    public function getNationalIdCode(): ?string
    {
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode): void
    {
        $this->nationalIdCode = $nationalIdCode;
    }

    public function getOrganizationRegCode(): ?string
    {
        return $this->organizationRegCode;
    }

    public function setOrganizationRegCode(?string $organizationRegCode): void
    {
        $this->organizationRegCode = $organizationRegCode;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): void
    {
        $this->bic = $bic;
    }

    public function getSourceIdentifier(): ?string
    {
        return $this->sourceIdentifier;
    }

    public function setSourceIdentifier(?string $sourceIdentifier): void
    {
        $this->sourceIdentifier = $sourceIdentifier;
    }

    public function getBankReference(): ?string
    {
        return $this->bankReference;
    }

    public function setBankReference(?string $bankReference): void
    {
        $this->bankReference = $bankReference;
    }

    public function getProcessorReference(): ?string
    {
        return $this->processorReference;
    }

    public function setProcessorReference(?string $processorReference): void
    {
        $this->processorReference = $processorReference;
    }

    public function getLegacyPaymentId(): ?string
    {
        return $this->legacyPaymentId;
    }

    public function setLegacyPaymentId(?string $legacyPaymentId): void
    {
        $this->legacyPaymentId = $legacyPaymentId;
    }

    public function getBookingDate(): ?DateTimeImmutable
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?DateTimeImmutable $bookingDate): void
    {
        $this->bookingDate = $bookingDate;
    }

    public function getInitiatedAt(): ?DateTimeImmutable
    {
        return $this->initiatedAt;
    }

    public function setInitiatedAt(?DateTimeImmutable $initiatedAt): void
    {
        $this->initiatedAt = $initiatedAt;
    }

    public function getCapturedAt(): ?DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function setCapturedAt(?DateTimeImmutable $capturedAt): void
    {
        $this->capturedAt = $capturedAt;
    }

    public function getAuthorizedAt(): ?DateTimeImmutable
    {
        return $this->authorizedAt;
    }

    public function setAuthorizedAt(?DateTimeImmutable $authorizedAt): void
    {
        $this->authorizedAt = $authorizedAt;
    }
}
