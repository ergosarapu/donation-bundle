<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
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

    private function mergePersonName(DateTimeImmutable $currentTime, ClaimId $claimId, ?PersonName $personName): MergeAttempt
    {
        if ($personName === null) {
            return MergeAttempt::noChange();
        }

        if ($personName->equals($this->personName)) {
            return MergeAttempt::noChange();
        }

        if ($this->personName !== null) {
            return MergeAttempt::conflict();
        }

        return MergeAttempt::changed(
            new IdentityPersonNameChanged($currentTime, $claimId, $this->id, $personName)
        );
    }

    private function mergeNationalIdCode(DateTimeImmutable $currentTime, ClaimId $claimId, ?NationalIdCode $nationalIdCode): MergeAttempt
    {
        if ($nationalIdCode === null) {
            return MergeAttempt::noChange();
        }

        if ($nationalIdCode->equals($this->nationalIdCode)) {
            return MergeAttempt::noChange();
        }

        if ($this->nationalIdCode !== null) {
            return MergeAttempt::conflict();
        }

        return MergeAttempt::changed(
            new IdentityNationalIdCodeChanged($currentTime, $claimId, $this->id, $nationalIdCode)
        );
    }

    private function mergeRawName(DateTimeImmutable $currentTime, ClaimId $claimId, ?RawName $rawName): MergeAttempt
    {
        if ($rawName === null) {
            return MergeAttempt::noChange();
        }

        if (isset($this->rawNames[$rawName->toString()])) {
            return MergeAttempt::noChange();
        }

        return MergeAttempt::changed(
            new IdentityRawNameAdded($currentTime, $claimId, $this->id, $rawName)
        );
    }

    private function mergeEmail(DateTimeImmutable $currentTime, ClaimId $claimId, ?Email $email): MergeAttempt
    {
        if ($email === null) {
            return MergeAttempt::noChange();
        }

        if (isset($this->emails[$email->toString()])) {
            return MergeAttempt::noChange();
        }

        return MergeAttempt::changed(
            new IdentityEmailAdded($currentTime, $claimId, $this->id, $email)
        );
    }

    private function mergeIban(DateTimeImmutable $currentTime, ClaimId $claimId, ?Iban $iban): MergeAttempt
    {
        if ($iban === null) {
            return MergeAttempt::noChange();
        }

        if (isset($this->ibans[$iban->value])) {
            return MergeAttempt::noChange();
        }

        return MergeAttempt::changed(
            new IdentityIbanAdded($currentTime, $claimId, $this->id, $iban)
        );
    }

    private function collectMergeAttempts(
        DateTimeImmutable $currentTime,
        ClaimId $claimId,
        ?PersonName $personName,
        ?NationalIdCode $nationalIdCode,
        ?RawName $rawName,
        ?Email $email,
        ?Iban $iban,
    ): array {
        return [
            $this->mergePersonName($currentTime, $claimId, $personName),
            $this->mergeNationalIdCode($currentTime, $claimId, $nationalIdCode),
            $this->mergeRawName($currentTime, $claimId, $rawName),
            $this->mergeEmail($currentTime, $claimId, $email),
            $this->mergeIban($currentTime, $claimId, $iban),
        ];
    }

    /** @param list<MergeAttempt> $attempts */
    private function hasConflicts(array $attempts): bool
    {
        return [] !== array_filter(
            $attempts,
            static fn (MergeAttempt $attempt): bool => $attempt->hasConflict,
        );
    }

    /** @param list<MergeAttempt> $attempts */
    private function recordAttemptEvents(array $attempts): void
    {
        $attemptsWithEvents = array_filter(
            $attempts,
            static fn (MergeAttempt $attempt): bool => $attempt->event !== null,
        );

        array_walk(
            $attemptsWithEvents,
            function (MergeAttempt $attempt): void {
                /** @var object $event */
                $event = $attempt->event;
                $this->recordThat($event);
            },
        );
    }

    public function mergeClaimData(
        DateTimeImmutable $currentTime,
        ClaimId $claimId,
        ?PersonName $personName,
        ?NationalIdCode $nationalIdCode,
        ?RawName $rawName,
        ?Email $email,
        ?Iban $iban,
    ): MergeResult {
        $attempts = $this->collectMergeAttempts($currentTime, $claimId, $personName, $nationalIdCode, $rawName, $email, $iban);

        // Check for conflicts
        if ($this->hasConflicts($attempts)) {
            return MergeResult::conflict();
        }

        $this->recordAttemptEvents($attempts);
        $this->recordThat(new ClaimMerged($currentTime, $claimId, $this->id));

        return MergeResult::success();
    }

    #[Apply]
    protected function applyIdentityCreated(IdentityCreated $event): void
    {
        $this->id = $event->identityId;
    }

    #[Apply]
    protected function applyIdentityNameChanged(IdentityRawNameAdded $event): void
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

    #[Apply]
    protected function applyClaimMerged(ClaimMerged $event): void
    {
    }
}
