<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMatch;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;

class Payment
{
    private string $paymentId;
    private int $amount;
    private string $currency;
    private PaymentStatus $status;
    private ?string $gateway = null;
    private ?string $redirectUrl;
    private ?PaymentImportStatus $importStatus = null;
    private ?string $reconciledWith = null;
    private ?string $appliedTo = null;
    private ?string $description = null;

    private ?string $givenName = null;
    private ?string $familyName = null;
    private ?string $accountHolderName = null;
    private ?string $effectiveName = null;

    private ?string $nationalIdCode = null;
    private ?string $organizationRegCode = null;
    private ?string $effectiveIdCode = null;

    private ?string $reference = null;
    private ?string $iban = null;
    private ?string $bic = null;
    private ?string $sourceIdentifier = null;
    private ?string $bankReference = null;
    private ?string $gatewayReference = null;
    private ?string $legacyPaymentNumber = null;

    private ?DateTimeImmutable $bookingDate = null;
    private ?DateTimeImmutable $initiatedAt = null;
    private ?DateTimeImmutable $capturedAt = null;
    private ?DateTimeImmutable $authorizedAt = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $effectiveDate;

    private DateTimeImmutable $updatedAt;

    /** @var array<PaymentMatch> */
    private array $matchingPayments = [];

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

    public function getImportStatus(): ?PaymentImportStatus
    {
        return $this->importStatus;
    }

    public function setImportStatus(?PaymentImportStatus $importStatus): void
    {
        $this->importStatus = $importStatus;
    }

    public function getReconciledWith(): ?string
    {
        return $this->reconciledWith;
    }

    public function setReconciledWith(?string $reconciledWith): void
    {
        $this->reconciledWith = $reconciledWith;
    }

    public function getAppliedTo(): ?string
    {
        return $this->appliedTo;
    }

    public function setAppliedTo(?string $appliedTo): void
    {
        $this->appliedTo = $appliedTo;
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
        $this->setEffectiveName();
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): void
    {
        $this->familyName = $familyName;
        $this->setEffectiveName();
    }

    public function getAccountHolderName(): ?string
    {
        return $this->accountHolderName;
    }

    public function setAccountHolderName(?string $accountHolderName): void
    {
        $this->accountHolderName = $accountHolderName;
        $this->setEffectiveName();
    }

    public function getNationalIdCode(): ?string
    {
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode): void
    {
        $this->nationalIdCode = $nationalIdCode;
        $this->setEffectiveIdCode();
    }

    public function getOrganizationRegCode(): ?string
    {
        return $this->organizationRegCode;
    }

    public function setOrganizationRegCode(?string $organizationRegCode): void
    {
        $this->organizationRegCode = $organizationRegCode;
        $this->setEffectiveIdCode();
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

    public function getGatewayReference(): ?string
    {
        return $this->gatewayReference;
    }

    public function setGatewayReference(?string $gatewayReference): void
    {
        $this->gatewayReference = $gatewayReference;
    }

    public function getLegacyPaymentNumber(): ?string
    {
        return $this->legacyPaymentNumber;
    }

    public function setLegacyPaymentNumber(?string $legacyPaymentNumber): void
    {
        $this->legacyPaymentNumber = $legacyPaymentNumber;
    }

    public function getBookingDate(): ?DateTimeImmutable
    {
        return $this->bookingDate;
    }

    public function setBookingDate(?DateTimeImmutable $bookingDate): void
    {
        $this->bookingDate = $bookingDate;
        $this->setEffectiveDate();
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

    public function getCapturedAt(): ?DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function setCapturedAt(?DateTimeImmutable $capturedAt): void
    {
        $this->capturedAt = $capturedAt;
        $this->setEffectiveDate();
    }

    public function getAuthorizedAt(): ?DateTimeImmutable
    {
        return $this->authorizedAt;
    }

    public function setAuthorizedAt(?DateTimeImmutable $authorizedAt): void
    {
        $this->authorizedAt = $authorizedAt;
        $this->setEffectiveDate();
    }

    /**
     * @return array<PaymentMatch>
     */
    public function getMatchingPayments(): array
    {
        return $this->matchingPayments;
    }

    /**
     * @param array<PaymentMatch> $matchingPayments
     */
    public function setMatchingPayments(array $matchingPayments): void
    {
        $this->matchingPayments = $matchingPayments;
    }

    public function getEffectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    private function setEffectiveDate(): void
    {
        $this->effectiveDate = $this->bookingDate ?? $this->capturedAt ?? $this->authorizedAt ?? $this->initiatedAt ?? $this->createdAt;
    }

    public function getEffectiveName(): ?string
    {
        return $this->effectiveName;
    }

    private function setEffectiveName(): void
    {
        if ($this->givenName !== null && $this->familyName !== null) {
            $this->effectiveName = trim($this->givenName . ' ' . $this->familyName);
        } else {
            $this->effectiveName = $this->accountHolderName;
        }
    }

    public function getEffectiveIdCode(): ?string
    {
        return $this->effectiveIdCode;
    }

    private function setEffectiveIdCode(): void
    {
        $this->effectiveIdCode = $this->nationalIdCode ?? $this->organizationRegCode;
    }
}
