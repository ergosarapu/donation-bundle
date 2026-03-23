<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'identity.iban_added')]
final class IdentityIbanAdded extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly ClaimId $claimId,
        public readonly IdentityId $identityId,
        #[PersonalData]
        public readonly Iban $iban,
    ) {
        parent::__construct($occuredOn);
    }
}
