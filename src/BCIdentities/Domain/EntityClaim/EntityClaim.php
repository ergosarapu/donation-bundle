<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'entity_claim')]
class EntityClaim extends BasicAggregateRoot
{
    #[Id]
    private EntityClaimId $id;
    private EntityClaimStatus $status;

    public static function create(
        DateTimeImmutable $currentTime,
        EntityClaimId $entityClaimId,
        EntityClaimSource $source,
        ?string $name,
        ?Email $email,
        ?Iban $iban,
        ?NationalIdCode $nationalIdCode,
    ): self {
        $entityClaim = new self();
        $entityClaim->recordThat(new EntityClaimCreated(
            $currentTime,
            $entityClaimId,
            $source,
            $name,
            $email,
            $iban,
            $nationalIdCode,
        ));
        return $entityClaim;
    }

    #[Apply]
    protected function applyCreated(EntityClaimCreated $event): void
    {
        $this->id = $event->entityClaimId;
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyLinkedToIdentity(EntityClaimLinkedToIdentity $event): void
    {
        $this->status = $event->status;
    }

    public function linkToIdentity(DateTimeImmutable $currentTime, IdentityId $identityId): void
    {
        if ($this->status === EntityClaimStatus::Linked) {
            return;
        }

        if ($this->status !== EntityClaimStatus::Pending) {
            throw new LogicException('Can only link a pending entity claim to an identity.');
        }

        $this->recordThat(new EntityClaimLinkedToIdentity($currentTime, $this->id, $identityId));
    }
}
