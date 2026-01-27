<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;

class Campaign
{
    private string $campaignId;
    private string $name;
    private string $publicTitle;
    private CampaignStatus $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPublicTitle(): string
    {
        return $this->publicTitle;
    }

    public function setPublicTitle(string $publicTitle): void
    {
        $this->publicTitle = $publicTitle;
    }

    public function getStatus(): CampaignStatus
    {
        return $this->status;
    }

    public function setStatus(CampaignStatus $status): void
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

    public function isActive(): bool
    {
        return $this->status === CampaignStatus::Active;
    }
}
