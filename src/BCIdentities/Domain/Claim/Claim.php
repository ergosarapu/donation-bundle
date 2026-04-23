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
    /** @var array<string, array<string, int>> */
    private const SCORE_LOOKUP = [
        LegalIdentifier::class => [
            ClaimEvidenceLevel::Observed->value => 9,
            ClaimEvidenceLevel::VerifiedByUser->value => 80,
            ClaimEvidenceLevel::Verified->value => 100,
        ],
        Iban::class => [
            ClaimEvidenceLevel::Observed->value => 8,
            ClaimEvidenceLevel::VerifiedByUser->value => 80,
            ClaimEvidenceLevel::Verified->value => 100,
        ],
        Email::class => [
            ClaimEvidenceLevel::Observed->value => 7,
            ClaimEvidenceLevel::VerifiedByUser->value => 80,
            ClaimEvidenceLevel::Verified->value => 100,
        ],
        PersonName::class => [
            ClaimEvidenceLevel::Observed->value => 6,
            ClaimEvidenceLevel::VerifiedByUser->value => 80,
            ClaimEvidenceLevel::Verified->value => 100,
        ],
        RawName::class => [
            ClaimEvidenceLevel::Observed->value => 5,
            ClaimEvidenceLevel::VerifiedByUser->value => 80,
            ClaimEvidenceLevel::Verified->value => 100,
        ],
    ];

    private const RESOLUTION_THRESHOLD = 80;

    #[Id]
    private ClaimId $id;
    private ClaimSource $source;
    private bool $inReview = false;
    /** @var array<string, Email|Iban|LegalIdentifier|PersonName|RawName|null> */
    private array $presentedValues = [];
    /** @var array<string, ClaimEvidenceLevel> */
    private array $presentedEvidenceLevels = [];
    private ?IdentityId $identityId = null;

    public static function create(
        DateTimeImmutable $currentTime,
        ClaimId $claimId,
        ClaimSource $source,
    ): self {
        $claim = new self();
        $claim->recordThat(new ClaimCreated(
            $currentTime,
            $claimId,
            $source,
        ));

        return $claim;
    }

    public function resolve(DateTimeImmutable $currentTime, IdentityId $identityId): void
    {
        // Idempotency check
        if ($this->identityId?->toString() === $identityId->toString()) {
            return;
        }

        if ($this->identityId !== null) {
            throw new LogicException('Claim is already resolved to another identity.');
        }

        if (!$this->isResolvable()) {
            throw new LogicException('Claim is not resolvable.');
        }

        $this->recordThat(new ClaimResolved($currentTime, $this->id, $this->source, $identityId));
    }

    public function markInReview(DateTimeImmutable $currentTime, ClaimReviewReason $reason): void
    {
        if ($this->inReview) {
            return;
        }

        if ($this->identityId !== null) {
            throw new LogicException('Cannot mark claim in review when it is already resolved.');
        }

        $this->recordThat(new ClaimInReview($currentTime, $this->id, $this->source, $reason));
    }

    public function personName(): ?PersonName
    {
        $value = $this->resolvableValue(PersonName::class);

        return $value instanceof PersonName ? $value : null;
    }

    public function rawName(): ?RawName
    {
        $value = $this->resolvableValue(RawName::class);

        return $value instanceof RawName ? $value : null;
    }

    public function email(): ?Email
    {
        $value = $this->resolvableValue(Email::class);

        return $value instanceof Email ? $value : null;
    }

    public function iban(): ?Iban
    {
        $value = $this->resolvableValue(Iban::class);

        return $value instanceof Iban ? $value : null;
    }

    public function legalIdentifier(): ?LegalIdentifier
    {
        $value = $this->resolvableValue(LegalIdentifier::class);

        return $value instanceof LegalIdentifier ? $value : null;
    }

    #[Apply]
    protected function applyClaimCreated(ClaimCreated $event): void
    {
        $this->id = $event->claimId;
        $this->source = $event->source;
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
    protected function applyClaimResolved(ClaimResolved $event): void
    {
        $this->inReview = false;
        $this->identityId = $event->identityId;
    }

    #[Apply]
    protected function applyClaimInReview(ClaimInReview $event): void
    {
        $this->inReview = true;
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

    private function scoreFor(string $className, ClaimEvidenceLevel $level): int
    {
        return self::SCORE_LOOKUP[$className][$level->value]
            ?? throw new LogicException(sprintf('Unsupported claim value class "%s".', $className));
    }

    private function meetsThreshold(string $className, int $threshold): bool
    {
        return $this->scoreFor($className, $this->evidenceLevelFor($className)) >= $threshold;
    }

    public function isResolvable(): bool
    {
        return [] === array_filter(
            $this->presentedValues,
            // The value may be null in case of personal data deletion
            fn (?object $value, string $className): bool => $value === null
                || !$this->meetsThreshold($className, self::RESOLUTION_THRESHOLD),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return LegalIdentifier|PersonName|RawName|Email|Iban|null
     */
    private function resolvableValue(string $key): ?object
    {
        $value = $this->value($key);
        if ($value === null) {
            return null;
        }
        if (!$this->meetsThreshold($key, self::RESOLUTION_THRESHOLD)) {
            return null;
        }
        return $value;
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

    private function evidenceLevelFor(string $className): ClaimEvidenceLevel
    {
        return $this->presentedEvidenceLevels[$className];
    }

}
