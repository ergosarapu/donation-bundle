<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\ClaimMerged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityEmailAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityIbanAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityNationalIdCodeChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityPersonNameChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityRawNameAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\MergeResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

final class IdentityTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;
    private IdentityId $identityId;
    private ClaimId $claimId;
    private PersonName $personName;
    private NationalIdCode $nationalIdCode;
    private RawName $rawName;
    private Email $email;
    private Iban $iban;

    protected function aggregateClass(): string
    {
        return Identity::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-01-01 10:00:00');
        $this->identityId = IdentityId::generate();
        $claimSource = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000001'));
        $this->claimId = ClaimId::generateDeterministic($claimSource);
        $this->personName = new PersonName('Jane', 'Doe');
        $this->nationalIdCode = new NationalIdCode('60001019906');
        $this->rawName = new RawName('Jane Doe');
        $this->email = new Email('jane@example.com');
        $this->iban = new Iban('EE471000001020145685');
    }

    public function testCreate(): void
    {
        $this->when(fn () => Identity::create($this->now, $this->identityId))
            ->then(new IdentityCreated($this->now, $this->identityId));
    }

    public function testMergeClaimDataWithNoValuesMergesClaim(): void
    {
        /** @var ?MergeResult $result */
        $result = null;

        $this->given(new IdentityCreated($this->now, $this->identityId))
            ->when(function (Identity $identity) use (&$result): void {
                $result = $identity->mergeClaimData($this->now, $this->claimId, null, null, null, null, null);
            })
            ->then(
                new ClaimMerged($this->now, $this->claimId, $this->identityId),
                function () use (&$result): void {
                    self::assertNotNull($result);
                    self::assertTrue($result->isSuccess());
                    self::assertFalse($result->isConflict());
                },
            );
    }

    public function testMergeClaimDataMergesClaimAndRecordsEventsForNewValues(): void
    {
        /** @var ?MergeResult $result */
        $result = null;

        $this->given(new IdentityCreated($this->now, $this->identityId))
            ->when(function (Identity $identity) use (&$result): void {
                $result = $identity->mergeClaimData(
                    $this->now,
                    $this->claimId,
                    $this->personName,
                    $this->nationalIdCode,
                    $this->rawName,
                    $this->email,
                    $this->iban,
                );
            })
            ->then(
                new IdentityPersonNameChanged($this->now, $this->claimId, $this->identityId, $this->personName),
                new IdentityNationalIdCodeChanged($this->now, $this->claimId, $this->identityId, $this->nationalIdCode),
                new IdentityRawNameAdded($this->now, $this->claimId, $this->identityId, $this->rawName),
                new IdentityEmailAdded($this->now, $this->claimId, $this->identityId, $this->email),
                new IdentityIbanAdded($this->now, $this->claimId, $this->identityId, $this->iban),
                new ClaimMerged($this->now, $this->claimId, $this->identityId),
                function () use (&$result): void {
                    self::assertNotNull($result);
                    self::assertTrue($result->isSuccess());
                    self::assertFalse($result->isConflict());
                },
            );
    }

    public function testMergeClaimDataWithExistingMatchingValuesMergesClaim(): void
    {
        /** @var ?MergeResult $result */
        $result = null;

        $this->given(
            new IdentityCreated($this->now, $this->identityId),
            new IdentityPersonNameChanged($this->now, $this->claimId, $this->identityId, $this->personName),
            new IdentityNationalIdCodeChanged($this->now, $this->claimId, $this->identityId, $this->nationalIdCode),
            new IdentityRawNameAdded($this->now, $this->claimId, $this->identityId, $this->rawName),
            new IdentityEmailAdded($this->now, $this->claimId, $this->identityId, $this->email),
            new IdentityIbanAdded($this->now, $this->claimId, $this->identityId, $this->iban),
        )
            ->when(function (Identity $identity) use (&$result): void {
                $result = $identity->mergeClaimData(
                    $this->now,
                    $this->claimId,
                    $this->personName,
                    $this->nationalIdCode,
                    $this->rawName,
                    $this->email,
                    $this->iban,
                );
            })
            ->then(
                new ClaimMerged($this->now, $this->claimId, $this->identityId),
                function () use (&$result): void {
                    self::assertNotNull($result);
                    self::assertTrue($result->isSuccess());
                    self::assertFalse($result->isConflict());
                },
            );
    }

    public function testMergeClaimDataReturnsConflictForDifferentPersonNameWithoutRecordingEvents(): void
    {
        /** @var ?MergeResult $result */
        $result = null;

        $this->given(
            new IdentityCreated($this->now, $this->identityId),
            new IdentityPersonNameChanged($this->now, $this->claimId, $this->identityId, $this->personName),
        )
            ->when(function (Identity $identity) use (&$result): void {
                $result = $identity->mergeClaimData(
                    $this->now,
                    $this->claimId,
                    new PersonName('Janet', 'Doe'),
                    null,
                    $this->rawName,
                    $this->email,
                    $this->iban,
                );
            })
            ->then(function () use (&$result): void {
                self::assertNotNull($result);
                self::assertFalse($result->isSuccess());
                self::assertTrue($result->isConflict());
            });
    }

    public function testMergeClaimDataReturnsConflictForDifferentNationalIdCodeWithoutRecordingEvents(): void
    {
        /** @var ?MergeResult $result */
        $result = null;

        $this->given(
            new IdentityCreated($this->now, $this->identityId),
            new IdentityNationalIdCodeChanged($this->now, $this->claimId, $this->identityId, $this->nationalIdCode),
        )
            ->when(function (Identity $identity) use (&$result): void {
                $result = $identity->mergeClaimData(
                    $this->now,
                    $this->claimId,
                    null,
                    new NationalIdCode('60001019917'),
                    $this->rawName,
                    $this->email,
                    $this->iban,
                );
            })
            ->then(function () use (&$result): void {
                self::assertNotNull($result);
                self::assertFalse($result->isSuccess());
                self::assertTrue($result->isConflict());
            });
    }
}
