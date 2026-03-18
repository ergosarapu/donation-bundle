<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;

#[Event(name: 'entity_claim.linked_to_identity')]
final class EntityClaimLinkedToIdentity extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly EntityClaimStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly EntityClaimId $entityClaimId,
        public readonly IdentityId $identityId,
    ) {
        parent::__construct($occuredOn);
        $this->status = EntityClaimStatus::Linked;
    }
}
