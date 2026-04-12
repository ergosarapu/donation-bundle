<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimInReview;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForIban;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForNationalIdCode;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForOrganisationRegCode;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForPersonName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForRawName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimResolved;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

final class ClaimTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;
    private ClaimSource $source;
    private PersonName $personName;
    private Email $email;
    private RawName $rawName;
    private Iban $iban;
    private NationalIdCode $nationalIdCode;
    private OrganisationRegCode $organisationRegCode;

    protected function aggregateClass(): string
    {
        return Claim::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-01-01 10:00:00');
        $this->source = ClaimSource::forPayment('018e1234-0000-7000-8000-000000000001');

        $this->personName = new PersonName('Jane', 'Doe');
        $this->email = new Email('example@example.com');
        $this->rawName = new RawName('Jane Doe');
        $this->iban = new Iban('EE382200221020145685');
        $this->nationalIdCode = new NationalIdCode('1234567890');
        $this->organisationRegCode = new OrganisationRegCode('12345678');
    }

    public function testCreate(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->when(fn () => Claim::create($this->now, $claimId, $this->source))
            ->then(new ClaimCreated($this->now, $claimId, $this->source));
    }

    public function testResolve(): void
    {
        $identityId = IdentityId::generate();
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->resolve($this->now, $identityId))
            ->then(new ClaimResolved($this->now, $claimId, $this->source, $identityId));
    }

    public function testResolveIsIdempotent(): void
    {
        $identityId = IdentityId::generate();
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimResolved($this->now, $claimId, $this->source, $identityId),
        )
            ->when(fn (Claim $claim) => $claim->resolve($this->now, $identityId))
            ->then();
    }

    public function testResolveAnotherIdentityThrows(): void
    {
        $identityId = IdentityId::generate();
        $anotherIdentityId = IdentityId::generate();
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimResolved($this->now, $claimId, $this->source, $identityId),
        )
            ->when(fn (Claim $claim) => $claim->resolve($this->now, $anotherIdentityId))
            ->expectsException(LogicException::class)
            ->expectsExceptionMessage('Claim is already resolved to another identity.');
    }

    public function testResolveNotResolvableClaimThrows(): void
    {
        $identityId = IdentityId::generate();
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $claim->resolve($this->now, $identityId))
            ->expectsException(LogicException::class)
            ->expectsExceptionMessage('Claim is not resolvable.');
    }

    public function testMarkInReview(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->markInReview($this->now, ClaimReviewReason::MultipleIdentityMatches))
            ->then(new ClaimInReview($this->now, $claimId, $this->source, ClaimReviewReason::MultipleIdentityMatches));
    }

    public function testMarkInReviewIsIdempotent(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimInReview($this->now, $claimId, $this->source, ClaimReviewReason::MultipleIdentityMatches),
        )
            ->when(fn (Claim $claim) => $claim->markInReview($this->now, ClaimReviewReason::MultipleIdentityMatches))
            ->then();
    }

    public function testMarkInReviewWhenResolvedThrows(): void
    {
        $identityId = IdentityId::generate();
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimResolved($this->now, $claimId, $this->source, $identityId),
        )
            ->when(fn (Claim $claim) => $claim->markInReview($this->now, ClaimReviewReason::MultipleIdentityMatches))
            ->expectsException(LogicException::class)
            ->expectsExceptionMessage('Cannot mark claim in review when it is already resolved.');
    }

    public function testPresentPersonName(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->personName, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Observed));
    }

    public function testPresentEmail(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->email, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForEmail($this->now, $claimId, $this->email, ClaimEvidenceLevel::Observed));
    }

    public function testPresentRawName(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->rawName, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForRawName($this->now, $claimId, $this->rawName, ClaimEvidenceLevel::Observed));
    }

    public function testPresentIban(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->iban, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForIban($this->now, $claimId, $this->iban, ClaimEvidenceLevel::Observed));
    }

    public function testPresentNationalIdCode(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->nationalIdCode, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForNationalIdCode($this->now, $claimId, $this->nationalIdCode, ClaimEvidenceLevel::Observed));
    }

    public function testPresentUpgradesEvidenceLevel(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->personName, ClaimEvidenceLevel::VerifiedByUser))
            ->then(new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::VerifiedByUser));
    }

    public function testPresentUnknownTypeThrows(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            /** @phpstan-ignore-next-line */
            ->when(fn (Claim $claim) => $claim->present($this->now, new class () {}, ClaimEvidenceLevel::Observed))
            ->expectsException(\TypeError::class);
    }

    public function testPresentDoesNotDowngradeEvidenceLevel(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::VerifiedByUser),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->personName, ClaimEvidenceLevel::Observed))
            ->then();
    }


    public function testPresentDifferentValueOverridesEvidenceLevel(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);
        $anotherPersonName = new PersonName('John', 'Smith');

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::VerifiedByUser),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, $anotherPersonName, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForPersonName($this->now, $claimId, $anotherPersonName, ClaimEvidenceLevel::Observed));
    }

    public function testPresentRegisteredTypeUpgradesExistingEvidenceLevel(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, PersonName::class, ClaimEvidenceLevel::VerifiedByUser))
            ->then(new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::VerifiedByUser))
        ;
    }

    public function testPresentingUnknownRegisteredTypeDoesNothing(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, PersonName::class, ClaimEvidenceLevel::VerifiedByUser))
            ->then();
    }

    public function testPersonNameValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->personName, $claim->personName()));
    }

    public function testPersonNameValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForPersonName($this->now, $claimId, $this->personName, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->personName()));
    }

    public function testEmailValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForEmail($this->now, $claimId, $this->email, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->email, $claim->email()));
    }

    public function testEmailValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForEmail($this->now, $claimId, $this->email, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->email()));
    }

    public function testRawNameValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForRawName($this->now, $claimId, $this->rawName, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->rawName, $claim->rawName()));
    }

    public function testRawNameValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForRawName($this->now, $claimId, $this->rawName, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->rawName()));
    }

    public function testIbanValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForIban($this->now, $claimId, $this->iban, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->iban, $claim->iban()));
    }

    public function testIbanValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForIban($this->now, $claimId, $this->iban, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->iban()));
    }

    public function testNationalIdCodeValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForNationalIdCode($this->now, $claimId, $this->nationalIdCode, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->nationalIdCode, $claim->nationalIdCode()));
    }

    public function testNationalIdCodeValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForNationalIdCode($this->now, $claimId, $this->nationalIdCode, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->nationalIdCode()));
    }

    public function testPresentOrganisationRegCode(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(new ClaimCreated($this->now, $claimId, $this->source))
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->organisationRegCode, ClaimEvidenceLevel::Observed))
            ->then(new ClaimPresentedForOrganisationRegCode($this->now, $claimId, $this->organisationRegCode, ClaimEvidenceLevel::Observed));
    }

    public function testOrganisationRegCodeValueOverThresholdReturnsValue(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForOrganisationRegCode($this->now, $claimId, $this->organisationRegCode, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $this->assertEquals($this->organisationRegCode, $claim->organisationRegCode()));
    }

    public function testOrganisationRegCodeValueUnderThresholdReturnsNull(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForOrganisationRegCode($this->now, $claimId, $this->organisationRegCode, ClaimEvidenceLevel::Observed),
        )
            ->when(fn (Claim $claim) => $this->assertNull($claim->organisationRegCode()));
    }

    public function testPresentNationalIdCodeWhenOrganisationRegCodeExistsThrowsLogicException(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForOrganisationRegCode($this->now, $claimId, $this->organisationRegCode, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->nationalIdCode, ClaimEvidenceLevel::Verified))
            ->expectsException(LogicException::class);
    }

    public function testPresentOrganisationRegCodeWhenNationalIdCodeExistsThrowsLogicException(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForNationalIdCode($this->now, $claimId, $this->nationalIdCode, ClaimEvidenceLevel::Verified),
        )
            ->when(fn (Claim $claim) => $claim->present($this->now, $this->organisationRegCode, ClaimEvidenceLevel::Verified))
            ->expectsException(LogicException::class);
    }

    public function testEmailPersonalDataDeleted(): void
    {
        $claimId = ClaimId::generateDeterministic($this->source);

        $email = null;

        $this->given(
            new ClaimCreated($this->now, $claimId, $this->source),
            new ClaimPresentedForEmail($this->now, $claimId, null, ClaimEvidenceLevel::Verified),
        )
            ->when(function (Claim $claim) use (&$email): void {
                $email = $claim->email();
            })
            ->then(function () use (&$email): void {
                self::assertNull($email);
            });
        ;
    }
}
