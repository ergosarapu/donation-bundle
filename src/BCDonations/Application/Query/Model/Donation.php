<?php

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;

class Donation
{
    private string $id;
    private string $paymentId;
    private int $amount;
    private string $currency;
    private DonationStatus $status;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
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
}
