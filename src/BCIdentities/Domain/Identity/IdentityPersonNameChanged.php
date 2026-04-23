<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'identity.person_name_changed')]
final class IdentityPersonNameChanged extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly ClaimId $claimId,
        public readonly IdentityId $identityId,
        #[PersonalData]
        public readonly ?PersonName $personName,
    ) {
        parent::__construct($occuredOn);
    }
}
