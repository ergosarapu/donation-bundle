<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
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
    /** @var array<class-string, array<string, int>> */
    private const SCORE_LOOKUP = [
        NationalIdCode::class => [
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
    /** @var array<class-string, ?object> */
    private array $presentedValues = [];
    /** @var array<class-string, ClaimEvidenceLevel> */
    private array $presentedEvidenceLevels = [];
    private ?IdentityId $identityId = null;

    public static function create(
        DateTimeImmutable $currentTime,
        ClaimSource $source,
    ): self {
        $claim = new self();
        $claim->recordThat(new ClaimCreated(
            $currentTime,
            ClaimId::generateDeterministic($source),
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
        return $this->resolvableTypedValue(PersonName::class);
    }

    public function rawName(): ?RawName
    {
        return $this->resolvableTypedValue(RawName::class);
    }

    public function email(): ?Email
    {
        return $this->resolvableTypedValue(Email::class);
    }

    public function iban(): ?Iban
    {
        return $this->resolvableTypedValue(Iban::class);
    }

    public function nationalIdCode(): ?NationalIdCode
    {
        return $this->resolvableTypedValue(NationalIdCode::class);
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
    protected function applyClaimPresentedForNationalIdCode(ClaimPresentedForNationalIdCode $event): void
    {
        $this->presentedValues[NationalIdCode::class] = $event->value;
        $this->presentedEvidenceLevels[NationalIdCode::class] = $event->evidenceLevel;
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

    /**
     * @param Email|RawName|Iban|PersonName|NationalIdCode $value
     * @param ClaimEvidenceLevel $evidenceLevel
     * @return bool
     */
    private function shouldPresent(
        object $value,
        ClaimEvidenceLevel $evidenceLevel,
    ): bool {
        $className = $value::class;
        $currentValue = $this->value($className);
        if ($currentValue === null) {
            return true;
        }
        $currentEvidenceLevel = $this->presentedEvidenceLevels[$className];
        if (!$currentValue->equals($value)) {
            return true;
        }
        if ($evidenceLevel->rank() <= $currentEvidenceLevel->rank()) {
            return false;
        }
        return true;
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
     * @template TValue of object
     * @param class-string<TValue> $className
     * @return TValue|null
     */
    private function resolvableTypedValue(string $className): ?object
    {
        $value = $this->value($className);

        if (!$value instanceof $className) {
            return null;
        }
        if ($this->meetsThreshold($className, self::RESOLUTION_THRESHOLD)) {
            return $value;
        }
        return null;
    }

    /**
     * @param Email|Iban|NationalIdCode|PersonName|RawName|class-string $value
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
     * @param class-string $className
     */
    private function upgradePresentedType(DateTimeImmutable $currentTime, string $className, ClaimEvidenceLevel $evidenceLevel): void
    {
        $value = $this->value($className);

        if ($value === null) {
            return;
        }

        $this->presentValue($currentTime, $value, $evidenceLevel);
    }


    /**
     * @param Email|Iban|NationalIdCode|PersonName|RawName $value
     */
    private function presentValue(DateTimeImmutable $currentTime, object $value, ClaimEvidenceLevel $evidenceLevel): void
    {
        $className = $value::class;

        if (!$this->shouldPresent($value, $evidenceLevel)) {
            return;
        }

        $event = match ($className) {
            PersonName::class => new ClaimPresentedForPersonName($currentTime, $this->id, $value, $evidenceLevel),
            RawName::class => new ClaimPresentedForRawName($currentTime, $this->id, $value, $evidenceLevel),
            Email::class => new ClaimPresentedForEmail($currentTime, $this->id, $value, $evidenceLevel),
            Iban::class => new ClaimPresentedForIban($currentTime, $this->id, $value, $evidenceLevel),
            NationalIdCode::class => new ClaimPresentedForNationalIdCode($currentTime, $this->id, $value, $evidenceLevel),
            default => throw new LogicException(sprintf('Unsupported claim value class "%s".', $className)),
        };

        $this->recordThat($event);
    }

    private function value(string $className): ?object
    {
        return $this->presentedValues[$className] ?? null;
    }

    private function evidenceLevelFor(string $className): ClaimEvidenceLevel
    {
        return $this->presentedEvidenceLevels[$className];
    }
}
