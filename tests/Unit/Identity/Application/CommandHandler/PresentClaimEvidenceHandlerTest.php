<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\ClaimSourceResolver;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\ClaimSourceCommandHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimSourceResolutionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCorrelated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class PresentClaimEvidenceHandlerTest extends TestCase
{
    public function testCreatesAndSavesReferencedClaimBeforeSavingCorrelation(): void
    {
        $claimRepository = $this->createMock(ClaimRepositoryInterface::class);
        $claimRepository->expects(self::exactly(2))->method('has')->willReturn(false);
        $savedEvents = [];
        $claimRepository->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(static function (Claim $claim) use (&$savedEvents): void {
                $savedEvents[] = $claim->releaseEvents();
            });
        $sourceResolutionRepository = $this->createMock(ClaimSourceResolutionRepositoryInterface::class);
        $sourceResolutionRepository->method('has')->willReturn(false);
        $sourceResolutionRepository->expects(self::exactly(2))->method('save');
        $clock = $this->createMock(ClockInterface::class);
        $handler = new ClaimSourceCommandHandler(
            $claimRepository,
            new ClaimSourceResolver($sourceResolutionRepository),
            $clock,
        );
        $now = new DateTimeImmutable('2026-01-01 10:00:00');
        $source = ClaimSource::create(ClaimSourceContext::Donation, 'source-1', 'donation');
        $correlatedSource = ClaimSource::create(ClaimSourceContext::Payment, 'source-2', 'payment');
        $command = new PresentClaimEvidence(
            $source,
            [],
            [$correlatedSource],
        );

        $clock->expects(self::once())->method('now')->willReturn($now);

        $handler->presentClaimEvidence($command);

        self::assertCount(1, $savedEvents[0]);
        self::assertInstanceOf(ClaimCreated::class, $savedEvents[0][0]);
        self::assertSame($correlatedSource, $savedEvents[0][0]->source);
        self::assertSame(
            $savedEvents[0][0]->claimId->toString(),
            $savedEvents[0][0]->initialIdentityId->toString(),
        );
        self::assertInstanceOf(ClaimCreated::class, $savedEvents[1][0]);
        self::assertInstanceOf(ClaimCorrelated::class, $savedEvents[1][1]);
        self::assertSame($savedEvents[0][0]->claimId, $savedEvents[1][1]->correlatedClaimId);
    }

    public function testReusesExistingReferencedClaim(): void
    {
        $claimRepository = $this->createMock(ClaimRepositoryInterface::class);
        $claimRepository->expects(self::exactly(2))
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true);
        $claimRepository->expects(self::once())->method('save');
        $sourceResolutionRepository = $this->createMock(ClaimSourceResolutionRepositoryInterface::class);
        $sourceResolutionRepository->method('has')->willReturn(false);
        $sourceResolutionRepository->expects(self::exactly(2))->method('save');
        $clock = $this->createMock(ClockInterface::class);
        $handler = new ClaimSourceCommandHandler(
            $claimRepository,
            new ClaimSourceResolver($sourceResolutionRepository),
            $clock,
        );

        $clock->expects(self::once())->method('now')->willReturn(new DateTimeImmutable('2026-01-01 10:00:00'));
        $handler->presentClaimEvidence(new PresentClaimEvidence(
            ClaimSource::create(ClaimSourceContext::Donation, 'source-1', 'donation'),
            [],
            [ClaimSource::create(ClaimSourceContext::Payment, 'source-2', 'payment')],
        ));
    }
}
