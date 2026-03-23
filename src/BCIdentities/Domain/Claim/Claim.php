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
    /** @var array<string, class-string> */
    public const VALUE_DISCRIMINATOR_TO_CLASS_MAP = [
        'personName' => PersonName::class,
        'rawName' => RawName::class,
        'email' => Email::class,
        'iban' => Iban::class,
        'nationalIdCode' => NationalIdCode::class,
    ];

    private const RESOLUTION_THRESHOLD = 80;

    #[Id]
    private ClaimId $id;
    private ClaimSource $source;
    private bool $inReview = false;
    /** @var array<class-string, object> */
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
        if ($this->identityId?->toString() === $identityId->toString()) {
            return;
        }

        if ($this->identityId !== null) {
            throw new LogicException('Claim is already resolved to another identity.');
        }

        $this->recordThat(new ClaimResolved($currentTime, $this->id, $this->source, $identityId));
    }

    public function markInReview(DateTimeImmutable $currentTime, ClaimReviewReason $reason): void
    {
        if ($this->identityId !== null || $this->inReview) {
            return;
        }

        $this->recordThat(new ClaimInReview($currentTime, $this->id, $this->source, $reason));
    }

    public function id(): ClaimId
    {
        return $this->id;
    }

    public function source(): ClaimSource
    {
        return $this->source;
    }

    public function personName(bool $requireResolutionThreshold = false): ?PersonName
    {
        return $this->typedValue(PersonName::class, $requireResolutionThreshold);
    }

    public function rawName(bool $requireResolutionThreshold = false): ?RawName
    {
        return $this->typedValue(RawName::class, $requireResolutionThreshold);
    }

    public function email(bool $requireResolutionThreshold = false): ?Email
    {
        return $this->typedValue(Email::class, $requireResolutionThreshold);
    }

    public function iban(bool $requireResolutionThreshold = false): ?Iban
    {
        return $this->typedValue(Iban::class, $requireResolutionThreshold);
    }

    public function nationalIdCode(bool $requireResolutionThreshold = false): ?NationalIdCode
    {
        return $this->typedValue(NationalIdCode::class, $requireResolutionThreshold);
    }

    #[Apply]
    protected function applyClaimCreated(ClaimCreated $event): void
    {
        $this->id = $event->claimId;
        $this->source = $event->source;
    }

    #[Apply]
    protected function applyClaimPresented(ClaimPresented $event): void
    {
        $this->presentedValues[$event->value::class] = $event->value;
        $this->presentedEvidenceLevels[$event->value::class] = $event->evidenceLevel;
    }

    #[Apply]
    protected function applyClaimResolved(ClaimResolved $event): void
    {
        $this->inReview = false;
        $this->reviewReason = null;
        $this->identityId = $event->identityId;
    }

    #[Apply]
    protected function applyClaimInReview(ClaimInReview $event): void
    {
        $this->inReview = true;
        $this->reviewReason = $event->reason;
    }

    private function shouldPresent(
        object|null $currentValue,
        object $newValue,
        ?ClaimEvidenceLevel $currentEvidenceLevel,
        ClaimEvidenceLevel $newEvidenceLevel,
    ): bool {
        if ($currentValue === null) {
            return true;
        }

        if ($currentValue == $newValue && $currentEvidenceLevel?->rank() >= $newEvidenceLevel->rank()) {
            return false;
        }

        return true;
    }

    private function scoreFor(string $className, ?ClaimEvidenceLevel $level): int
    {
        return match ($className) {
            NationalIdCode::class => match ($level) {
                ClaimEvidenceLevel::Observed => 9,
                ClaimEvidenceLevel::VerifiedByUser => 80,
                ClaimEvidenceLevel::Verified => 100,
                null => 0,
            },
            Iban::class => match ($level) {
                ClaimEvidenceLevel::Observed => 8,
                ClaimEvidenceLevel::VerifiedByUser => 80,
                ClaimEvidenceLevel::Verified => 100,
                null => 0,
            },
            Email::class => match ($level) {
                ClaimEvidenceLevel::Observed => 7,
                ClaimEvidenceLevel::VerifiedByUser => 80,
                ClaimEvidenceLevel::Verified => 100,
                null => 0,
            },
            PersonName::class => match ($level) {
                ClaimEvidenceLevel::Observed => 6,
                ClaimEvidenceLevel::VerifiedByUser => 80,
                ClaimEvidenceLevel::Verified => 100,
                null => 0,
            },
            RawName::class => match ($level) {
                ClaimEvidenceLevel::Observed => 5,
                ClaimEvidenceLevel::VerifiedByUser => 80,
                ClaimEvidenceLevel::Verified => 100,
                null => 0,
            },
        };
    }

    private function meetsThreshold(string $className, int $threshold): bool
    {
        return $this->scoreFor($className, $this->evidenceLevelFor($className)) >= $threshold;
    }

    public function allExistingAttributesExceedResolutionThreshold(): bool
    {
        foreach (self::VALUE_DISCRIMINATOR_TO_CLASS_MAP as $className) {
            if ($this->value($className) !== null && !$this->meetsThreshold($className, self::RESOLUTION_THRESHOLD)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @template TValue of object
     * @param class-string<TValue> $className
     * @param bool $requireResolutionThreshold
     * @return TValue|null
     */
    private function typedValue(string $className, bool $requireResolutionThreshold = false): ?object
    {
        $value = $this->value($className);

        if (!$value instanceof $className) {
            return null;
        }

        if ($requireResolutionThreshold && !$this->meetsThreshold($className, self::RESOLUTION_THRESHOLD)) {
            return null;
        }

        return $value;
    }

    public function present(DateTimeImmutable $currentTime, object|string $value, ClaimEvidenceLevel $evidenceLevel): bool
    {
        if (is_string($value)) {
            return $this->upgradePresentedType($currentTime, $value, $evidenceLevel);
        }

        return $this->presentValue($currentTime, $value, $evidenceLevel);
    }

    private function upgradePresentedType(DateTimeImmutable $currentTime, string $className, ClaimEvidenceLevel $evidenceLevel): bool
    {
        $value = $this->value($className);

        if ($value === null) {
            return false;
        }

        return $this->presentValue($currentTime, $value, $evidenceLevel);
    }

    private function presentValue(DateTimeImmutable $currentTime, object $value, ClaimEvidenceLevel $evidenceLevel): bool
    {
        $className = $value::class;
        $currentValue = $this->value($className);

        if (!$this->shouldPresent($currentValue, $value, $this->presentedEvidenceLevels[$className] ?? null, $evidenceLevel)) {
            return false;
        }

        $this->recordThat(new ClaimPresented(
            $currentTime,
            $this->id,
            $value,
            $evidenceLevel,
        ));

        return true;
    }

    private function value(string $className): ?object
    {
        return $this->presentedValues[$className] ?? null;
    }

    /**
     * @param array<class-string, ClaimEvidenceLevel> $overrides
     */
    private function evidenceLevelFor(string $className, array $overrides = []): ?ClaimEvidenceLevel
    {
        if (!isset($this->presentedValues[$className]) && !isset($overrides[$className])) {
            return null;
        }

        return $overrides[$className] ?? $this->presentedEvidenceLevels[$className] ?? null;
    }
}
