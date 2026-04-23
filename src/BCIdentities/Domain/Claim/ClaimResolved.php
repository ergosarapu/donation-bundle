<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;

#[Event(name: 'claim.resolved')]
final class ClaimResolved extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly ClaimId $claimId,
        public readonly ClaimSource $source,
        public readonly IdentityId $identityId,
    ) {
        parent::__construct($occuredOn);
    }
}
