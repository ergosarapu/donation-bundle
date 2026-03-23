<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Hydrator\MixedTypeObjectNormalizer;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'claim.presented')]
final class ClaimPresented extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly ClaimId $claimId,
        #[PersonalData]
        #[MixedTypeObjectNormalizer(Claim::VALUE_DISCRIMINATOR_TO_CLASS_MAP)]
        public readonly object $value,
        public readonly ClaimEvidenceLevel $evidenceLevel,
    ) {
        parent::__construct($occuredOn);
    }
}
