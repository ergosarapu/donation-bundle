<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'identity')]
final class Identity extends BasicAggregateRoot
{
    #[Id]
    private IdentityId $id;
    /** @var array<string, RawName> */
    private array $rawNames = [];
    private ?PersonName $personName = null;
    private ?NationalIdCode $nationalIdCode = null;
    /** @var array<string, Email> */
    private array $emails = [];
    /** @var array<string, Iban> */
    private array $ibans = [];

    public static function create(DateTimeImmutable $currentTime, IdentityId $identityId): self
    {
        $identity = new self();
        $identity->recordThat(new IdentityCreated($currentTime, $identityId));
        return $identity;
    }

    public function resolveClaim(DateTimeImmutable $currentTime, Claim $claim): ?ClaimReviewReason
    {
        $reviewReason = $this->resolveClaimReviewReason($claim);

        if ($reviewReason !== null) {
            return $reviewReason;
        }

        $claimId = $claim->id();

        if ($claim->rawName() !== null && !isset($this->rawNames[$claim->rawName()->toString()])) {
            $this->recordThat(new IdentityRawNameChanged($currentTime, $claimId, $this->id, $claim->rawName()));
        }

        if ($this->personName === null && $claim->personName() !== null) {
            $this->recordThat(new IdentityPersonNameChanged($currentTime, $claimId, $this->id, $claim->personName()));
        }

        if ($claim->email() !== null && !isset($this->emails[$claim->email()->toString()])) {
            $this->recordThat(new IdentityEmailAdded($currentTime, $claimId, $this->id, $claim->email()));
        }

        if ($claim->iban() !== null && !isset($this->ibans[$claim->iban()->value])) {
            $this->recordThat(new IdentityIbanAdded($currentTime, $claimId, $this->id, $claim->iban()));
        }

        if ($this->nationalIdCode === null && $claim->nationalIdCode() !== null) {
            $this->recordThat(new IdentityNationalIdCodeChanged($currentTime, $claimId, $this->id, $claim->nationalIdCode()));
        }

        $claim->resolve($currentTime, $this->id);

        return null;
    }

    private function resolveClaimReviewReason(Claim $claim): ?ClaimReviewReason
    {
        if (!$claim->allExistingAttributesExceedResolutionThreshold()) {
            return ClaimReviewReason::AttributeBelowThreshold;
        }

        if ($this->hasConflictingClaim($claim)) {
            return ClaimReviewReason::ConflictingClaimValue;
        }

        return null;
    }

    private function hasConflictingClaim(Claim $claim): bool
    {
        if ($this->personName !== null && $claim->personName() !== null && !$this->personName->equals($claim->personName())) {
            return true;
        }

        return $this->nationalIdCode !== null
            && $claim->nationalIdCode() !== null
            && !$this->nationalIdCode->equals($claim->nationalIdCode());
    }

    #[Apply]
    protected function applyIdentityCreated(IdentityCreated $event): void
    {
        $this->id = $event->identityId;
    }

    #[Apply]
    protected function applyIdentityNameChanged(IdentityRawNameChanged $event): void
    {
        $this->rawNames[$event->rawName->toString()] = $event->rawName;
    }

    #[Apply]
    protected function applyIdentityPersonNameChanged(IdentityPersonNameChanged $event): void
    {
        $this->personName = $event->personName;
    }

    #[Apply]
    protected function applyIdentityEmailAdded(IdentityEmailAdded $event): void
    {
        $this->emails[$event->email->toString()] = $event->email;
    }

    #[Apply]
    protected function applyIdentityIbanAdded(IdentityIbanAdded $event): void
    {
        $this->ibans[$event->iban->value] = $event->iban;
    }

    #[Apply]
    protected function applyIdentityNationalIdCodeChanged(IdentityNationalIdCodeChanged $event): void
    {
        $this->nationalIdCode = $event->nationalIdCode;
    }
}
