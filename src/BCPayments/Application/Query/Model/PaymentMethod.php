<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;

class PaymentMethod
{
    private string $paymentMethodId;
    private string $createdFor;
    private ?PaymentMethodUnusableReason $unusableReason = null;
    private DateTimeImmutable $updatedAt;

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function setCreatedFor(string $createdFor): void
    {
        $this->createdFor = $createdFor;
    }

    public function setUnusableReason(?PaymentMethodUnusableReason $unusableReason): void
    {
        $this->unusableReason = $unusableReason;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getCreatedFor(): string
    {
        return $this->createdFor;
    }

    public function getUnusableReason(): ?PaymentMethodUnusableReason
    {
        return $this->unusableReason;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }


}
