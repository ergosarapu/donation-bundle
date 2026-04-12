<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'claim.presented_for_organisation_reg_code')]
final class ClaimPresentedForOrganisationRegCode extends AbstractTimestampedEvent implements DomainEventInterface
{
    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly ClaimId $claimId,
        #[PersonalData]
        public readonly ?OrganisationRegCode $value,
        public readonly ClaimEvidenceLevel $evidenceLevel,
    ) {
        parent::__construct($occuredOn);
    }
}
