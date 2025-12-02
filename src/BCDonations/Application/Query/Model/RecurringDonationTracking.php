<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model;

/**
 * Tracking entity to prevent double-processing of RecurringDonationAccepted events
 */
class RecurringDonationTracking
{
    private string $donationId;
    private bool $donationAcceptedSeen;

    public function getDonationId(): string
    {
        return $this->donationId;
    }

    public function setDonationId(string $donationId): void
    {
        $this->donationId = $donationId;
    }

    public function isDonationAcceptedSeen(): bool
    {
        return $this->donationAcceptedSeen;
    }

    public function setDonationAcceptedSeen(bool $donationAcceptedSeen): void
    {
        $this->donationAcceptedSeen = $donationAcceptedSeen;
    }
}
