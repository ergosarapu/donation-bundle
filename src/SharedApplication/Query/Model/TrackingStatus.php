<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Model;

use DateTimeImmutable;

class TrackingStatus
{
    private string $trackingId;
    private DateTimeImmutable $updatedAt;
    private ?string $paymentId = null;
    private ?string $paymentMethodId = null;
    private ?string $donationId = null;

    public function setTrackingId(string $trackingId): self
    {
        $this->trackingId = $trackingId;
        return $this;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function setPaymentId(?string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    public function setPaymentMethodId(?string $paymentMethodId): self
    {
        $this->paymentMethodId = $paymentMethodId;
        return $this;
    }

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function getDonationId(): ?string
    {
        return $this->donationId;
    }

    public function setDonationId(?string $donationId): self
    {
        $this->donationId = $donationId;
        return $this;
    }
}
