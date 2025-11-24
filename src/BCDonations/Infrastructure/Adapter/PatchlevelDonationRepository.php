<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Repository\PatchlevelRepositoryWrapperTrait;

final class PatchlevelDonationRepository implements DonationRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(Donation $donation): void
    {
        $this->saveAggregate($donation);
    }

    public function load(DonationId $donationId): Donation
    {
        /** @var Donation $donation */
        $donation = $this->loadAggregate($donationId);
        return $donation;
    }

    public function has(DonationId $donationId): bool
    {
        return $this->hasAggregate($donationId);
    }
}
