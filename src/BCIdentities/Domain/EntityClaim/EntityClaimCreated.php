<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\Event\AbstractTimestampedEvent;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;

#[Event(name: 'entity_claim.created')]
final class EntityClaimCreated extends AbstractTimestampedEvent implements DomainEventInterface
{
    public readonly EntityClaimStatus $status;

    public function __construct(
        DateTimeImmutable $occuredOn,
        #[DataSubjectId]
        public readonly EntityClaimId $entityClaimId,
        public readonly EntityClaimSource $source,
        #[PersonalData]
        public readonly ?string $name,
        #[PersonalData]
        public readonly ?Email $email,
        #[PersonalData]
        public readonly ?Iban $iban,
        #[PersonalData]
        public readonly ?NationalIdCode $nationalIdCode,
    ) {
        parent::__construct($occuredOn);
        $this->status = EntityClaimStatus::Pending;
    }
}
