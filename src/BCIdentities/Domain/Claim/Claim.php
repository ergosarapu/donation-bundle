<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'claim')]
final class Claim extends BasicAggregateRoot
{
    
    #[Id]
    private ClaimId $id;
    /** @var array<string, ClaimSource> */
    private array $correlations = [];
    /** @var array<string, Email|Iban|LegalIdentifier|PersonName|RawName|null> */
    private array $presentedValues = [];
    /** @var array<string, ClaimEvidenceLevel> */
    private array $presentedEvidenceLevels = [];

    public static function create(
        DateTimeImmutable $currentTime,
        ClaimId $claimId,
        ClaimSource $source,
        IdentityId $initialIdentityId,
    ): self
    {
        $claim = new self();
        $claim->recordThat(new ClaimCreated($currentTime, $claimId, $source, $initialIdentityId));

        return $claim;
    }

    #[Apply]
    protected function applyClaimCreated(ClaimCreated $event): void
    {
        $this->id = $event->claimId;
        $this->correlations = [];
    }


    #[Apply]
    protected function applyClaimPresentedForPersonName(ClaimPresentedForPersonName $event): void
    {
        $this->presentedValues[PersonName::class] = $event->value;
        $this->presentedEvidenceLevels[PersonName::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimPresentedForRawName(ClaimPresentedForRawName $event): void
    {
        $this->presentedValues[RawName::class] = $event->value;
        $this->presentedEvidenceLevels[RawName::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimPresentedForEmail(ClaimPresentedForEmail $event): void
    {
        $this->presentedValues[Email::class] = $event->value;
        $this->presentedEvidenceLevels[Email::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimPresentedForIban(ClaimPresentedForIban $event): void
    {
        $this->presentedValues[Iban::class] = $event->value;
        $this->presentedEvidenceLevels[Iban::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimPresentedForLegalIdentifier(ClaimPresentedForLegalIdentifier $event): void
    {
        $this->presentedValues[LegalIdentifier::class] = $event->value;
        $this->presentedEvidenceLevels[LegalIdentifier::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimCorrelated(ClaimCorrelated $event): void
    {
        $this->correlations[$this->correlatedSourceKey($event->correlatedSource)] = $event->correlatedSource;
    }

    #[Apply]
    protected function applyClaimCorrelationRemoved(ClaimCorrelationRemoved $event): void
    {
        unset($this->correlations[$this->correlatedSourceKey($event->correlatedSource)]);
    }

    private function correlatedSourceKey(ClaimSource $source): string
    {
        return $source->context->value . ':' . $source->id;
    }

    private function shouldPresent(
        Email|Iban|LegalIdentifier|PersonName|RawName $value,
        ClaimEvidenceLevel $evidenceLevel,
    ): bool {
        $currentValue = $this->value($value::class);
        if ($currentValue === null) {
            return true;
        }
        if (!$currentValue->equals($value)) {
            return true;
        }
        $currentEvidenceLevel = $this->presentedEvidenceLevels[$value::class];
        if ($evidenceLevel->rank() > $currentEvidenceLevel->rank()) {
            return true;
        }
        return false;
    }

    /**
     * @param PersonName|RawName|Email|Iban|LegalIdentifier|string $value
     */
    public function present(DateTimeImmutable $currentTime, object|string $value, ClaimEvidenceLevel $evidenceLevel): void
    {
        if (is_string($value)) {
            $this->upgradePresentedType($currentTime, $value, $evidenceLevel);

            return;
        }

        $this->presentValue($currentTime, $value, $evidenceLevel);
    }

    public function correlate(
        DateTimeImmutable $currentTime,
        ClaimSource $correlatedSource,
        ClaimId $correlatedClaimId,
    ): void
    {
        if ($correlatedClaimId->toString() === $this->id->toString()) {
            return;
        }

        if ($this->correlations[$this->correlatedSourceKey($correlatedSource)] ?? false) {
            return;
        }

        $this->recordThat(new ClaimCorrelated(
            $currentTime,
            $this->id,
            $correlatedSource,
            $correlatedClaimId,
        ));
    }
    
    /**
     * @param string $className
     */
    private function upgradePresentedType(DateTimeImmutable $currentTime, string $className, ClaimEvidenceLevel $evidenceLevel): void
    {
        $value = $this->value($className);

        if ($value === null) {
            return;
        }

        $this->presentValue($currentTime, $value, $evidenceLevel);
    }

    private function presentValue(DateTimeImmutable $currentTime, Email|Iban|LegalIdentifier|PersonName|RawName $value, ClaimEvidenceLevel $evidenceLevel): void
    {
        if (!$this->shouldPresent($value, $evidenceLevel)) {
            return;
        }

        if ($value instanceof PersonName) {
            $event = new ClaimPresentedForPersonName($currentTime, $this->id, $value, $evidenceLevel);
        } elseif ($value instanceof RawName) {
            $event = new ClaimPresentedForRawName($currentTime, $this->id, $value, $evidenceLevel);
        } elseif ($value instanceof Email) {
            $event = new ClaimPresentedForEmail($currentTime, $this->id, $value, $evidenceLevel);
        } elseif ($value instanceof Iban) {
            $event = new ClaimPresentedForIban($currentTime, $this->id, $value, $evidenceLevel);
        } else {
            $event = new ClaimPresentedForLegalIdentifier($currentTime, $this->id, $value, $evidenceLevel);
        }

        $this->recordThat($event);
    }

    private function value(string $className): Email|Iban|LegalIdentifier|PersonName|RawName|null
    {
        return $this->presentedValues[$className] ?? null;
    }
}
