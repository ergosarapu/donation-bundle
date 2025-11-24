<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringDonationRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Repository\PatchlevelRepositoryWrapperTrait;

final class PatchlevelRecurringDonationRepository implements RecurringDonationRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(RecurringDonation $donation): void
    {
        $this->saveAggregate($donation);
    }

    public function load(RecurringDonationId $recurringDonationId): RecurringDonation
    {
        /** @var RecurringDonation $recurringDonation */
        $recurringDonation = $this->loadAggregate($recurringDonationId);
        return $recurringDonation;
    }

    public function has(RecurringDonationId $recurringDonationId): bool
    {
        return $this->hasAggregate($recurringDonationId);
    }
}
