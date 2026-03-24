<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\ResolveClaimHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityLookupInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimInReview;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForIban;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForPersonName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimResolved;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimReviewReason;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\ClaimMerged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityIbanAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityPersonNameChanged;
use ErgoSarapu\DonationBundle\SharedApplication\Port\TransactionManagerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\ClaimId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class ResolveClaimHandlerTest extends TestCase
{
    private ClaimRepositoryInterface&MockObject $claimRepository;
    private IdentityLookupInterface&MockObject $identityLookup;
    private IdentityRepositoryInterface&MockObject $identityRepository;
    private TransactionManagerInterface&MockObject $transactionManager;
    private ResolveClaimHandler $handler;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->claimRepository = $this->createMock(ClaimRepositoryInterface::class);
        $this->identityLookup = $this->createMock(IdentityLookupInterface::class);
        $this->identityRepository = $this->createMock(IdentityRepositoryInterface::class);
        $this->transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->now = new DateTimeImmutable('2026-01-01 10:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ResolveClaimHandler(
            $this->claimRepository,
            $this->identityLookup,
            $this->identityRepository,
            $this->transactionManager,
            $clock,
        );
    }

    public function testMarksClaimInReviewWhenMultipleIdentityMatchesFound(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000011'));
        $claimId = ClaimId::generateDeterministic($source);
        $claim = $this->claimWithIban($source, $claimId, new Iban('EE471000001020145685'));
        $command = new ResolveClaim($source);
        $identityIdA = IdentityId::generate();
        $identityIdB = IdentityId::generate();

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        $this->identityLookup->expects($this->once())
            ->method('lookup')
            ->with(
                null,
                new Iban('EE471000001020145685'),
                null,
            )
            ->willReturn([$identityIdA, $identityIdB]);

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim)
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->identityRepository->expects($this->never())->method('has');
        $this->identityRepository->expects($this->never())->method('load');
        $this->identityRepository->expects($this->never())->method('save');
        $this->transactionManager->expects($this->never())->method('transactional');

        ($this->handler)($command);

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertEquals(
            [new ClaimInReview($this->now, $claimId, $source, ClaimReviewReason::MultipleIdentityMatches)],
            $savedClaim->releaseEvents(),
        );
    }

    public function testMarksClaimInReviewWhenIdentityMergeConflicts(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000012'));
        $claimId = ClaimId::generateDeterministic($source);
        $identityId = IdentityId::generate();
        $claim = Claim::createFromEvents([
            new ClaimCreated($this->now, $claimId, $source),
            new ClaimPresentedForEmail($this->now, $claimId, new Email('jane@example.com'), ClaimEvidenceLevel::Verified),
            new ClaimPresentedForPersonName($this->now, $claimId, new PersonName('Jane', 'Doe'), ClaimEvidenceLevel::Verified),
        ]);
        $identity = Identity::createFromEvents([
            new IdentityCreated($this->now, $identityId),
            new IdentityPersonNameChanged($this->now, $claimId, $identityId, new PersonName('Janet', 'Doe')),
        ]);
        $command = new ResolveClaim($source);

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        $this->identityLookup->expects($this->once())
            ->method('lookup')
            ->with(
                new Email('jane@example.com'),
                null,
                null,
            )
            ->willReturn([$identityId]);

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->with($identityId)
            ->willReturn(true);

        $this->identityRepository->expects($this->once())
            ->method('load')
            ->with($identityId)
            ->willReturn($identity);

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim)
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->identityRepository->expects($this->never())->method('save');
        $this->transactionManager->expects($this->never())->method('transactional');

        ($this->handler)($command);

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertEquals(
            [new ClaimInReview($this->now, $claimId, $source, ClaimReviewReason::MergeConflict)],
            $savedClaim->releaseEvents(),
        );
    }

    public function testLoadsIdentityAndResolvesClaimTransactionally(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000013'));
        $claimId = ClaimId::generateDeterministic($source);
        $identityId = IdentityId::generate();
        $iban = new Iban('EE471000001020145685');
        $claim = $this->claimWithIban($source, $claimId, $iban);
        $identity = Identity::createFromEvents([
            new IdentityCreated($this->now, $identityId),
        ]);
        $command = new ResolveClaim($source);

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        $this->identityLookup->expects($this->once())
            ->method('lookup')
            ->with(null, $iban, null)
            ->willReturn([$identityId]);

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->with($identityId)
            ->willReturn(true);

        $this->identityRepository->expects($this->once())
            ->method('load')
            ->with($identityId)
            ->willReturn($identity);

        /** @var ?Identity $savedIdentity */
        $savedIdentity = null;
        $this->identityRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Identity::class))
            ->willReturnCallback(static function (Identity $identity) use (&$savedIdentity): void {
                $savedIdentity = $identity;
            });

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim)
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->transactionManager->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $callback): void {
                $callback();
            });

        ($this->handler)($command);

        self::assertInstanceOf(Identity::class, $savedIdentity);
        self::assertEquals(
            [
                new IdentityIbanAdded($this->now, $claimId, $identityId, $iban),
                new ClaimMerged($this->now, $claimId, $identityId),
            ],
            $savedIdentity->releaseEvents(),
        );

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertEquals(
            [new ClaimResolved($this->now, $claimId, $source, $identityId)],
            $savedClaim->releaseEvents(),
        );
    }

    public function testCreatesIdentityAndResolvesClaimTransactionallyWhenLookupHasNoMatches(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000014'));
        $claimId = ClaimId::generateDeterministic($source);
        $iban = new Iban('EE471000001020145686');
        $claim = $this->claimWithIban($source, $claimId, $iban);
        $command = new ResolveClaim($source);

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        $this->identityLookup->expects($this->once())
            ->method('lookup')
            ->with(null, $iban, null)
            ->willReturn([]);

        $this->identityRepository->expects($this->once())->method('has')->willReturn(false);
        $this->identityRepository->expects($this->never())->method('load');

        /** @var ?Identity $savedIdentity */
        $savedIdentity = null;
        $this->identityRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Identity::class))
            ->willReturnCallback(static function (Identity $identity) use (&$savedIdentity): void {
                $savedIdentity = $identity;
            });

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim)
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->transactionManager->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(static function (callable $callback): void {
                $callback();
            });

        ($this->handler)($command);

        self::assertInstanceOf(Identity::class, $savedIdentity);
        $identityEvents = $savedIdentity->releaseEvents();
        self::assertCount(3, $identityEvents);
        self::assertInstanceOf(IdentityCreated::class, $identityEvents[0]);
        self::assertInstanceOf(IdentityIbanAdded::class, $identityEvents[1]);
        self::assertInstanceOf(ClaimMerged::class, $identityEvents[2]);

        $resolvedIdentityId = $identityEvents[0]->identityId;
        self::assertEquals(new IdentityIbanAdded($this->now, $claimId, $resolvedIdentityId, $iban), $identityEvents[1]);
        self::assertEquals(new ClaimMerged($this->now, $claimId, $resolvedIdentityId), $identityEvents[2]);

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertEquals(
            [new ClaimResolved($this->now, $claimId, $source, $resolvedIdentityId)],
            $savedClaim->releaseEvents(),
        );
    }

    private function claimWithIban(ClaimSource $source, ClaimId $claimId, Iban $iban): Claim
    {
        return Claim::createFromEvents([
            new ClaimCreated($this->now, $claimId, $source),
            new ClaimPresentedForIban($this->now, $claimId, $iban, ClaimEvidenceLevel::Verified),
        ]);
    }
}
