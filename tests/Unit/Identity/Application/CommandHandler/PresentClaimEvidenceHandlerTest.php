<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\PresentClaimEvidenceHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\ClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\ValueObject\ClaimPresentation;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ClaimSource;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class PresentClaimEvidenceHandlerTest extends TestCase
{
    private ClaimRepositoryInterface&MockObject $claimRepository;
    private CommandBusInterface&MockObject $commandBus;
    private PresentClaimEvidenceHandler $handler;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->claimRepository = $this->createMock(ClaimRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->now = new DateTimeImmutable('2026-01-01 10:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new PresentClaimEvidenceHandler($this->claimRepository, $this->commandBus, $clock);
    }

    public function testCreatesClaimAndDispatchesResolve(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000001'));
        $claimId = ClaimId::generateDeterministic($source);
        $command = new PresentClaimEvidence(
            source: $source,
            presentations: [ClaimPresentation::forValue(new Iban('EE471000001020145685'), ClaimEvidenceLevel::Verified)],
        );

        $this->claimRepository->expects($this->once())
            ->method('has')
            ->with($claimId)
            ->willReturn(false);

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new ResolveClaim($claimId))
            ->willReturn(new CommandResult(null, 'correlation-id'));

        ($this->handler)($command);

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertEquals(new Iban('EE471000001020145685'), $savedClaim->iban());
    }

    public function testLoadsClaimAndDispatchesResolve(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000002'));
        $claimId = ClaimId::generateDeterministic($source);
        $claim = Claim::create($this->now, $claimId, $source);
        $claim->releaseEvents();
        $command = new PresentClaimEvidence(
            source: $source,
            presentations: [ClaimPresentation::forValue(new Iban('EE471000001020145685'), ClaimEvidenceLevel::Verified)],
        );

        $this->claimRepository->expects($this->once())
            ->method('has')
            ->with($claimId)
            ->willReturn(true);

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->with($claim)
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new ResolveClaim($claimId))
            ->willReturn(new CommandResult(null, 'correlation-id'));

        ($this->handler)($command);

        self::assertSame($claim, $savedClaim);
        self::assertEquals(new Iban('EE471000001020145685'), $savedClaim->iban());
    }

    public function testClaimAggregateNotSavedWhenEvidenceDoesNotChangeState(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000003'));
        $claimId = ClaimId::generateDeterministic($source);
        $claim = Claim::create($this->now, $claimId, $source);
        $claim->present($this->now, new Iban('EE471000001020145685'), ClaimEvidenceLevel::Verified);
        $claim->releaseEvents();
        $command = new PresentClaimEvidence(
            source: $source,
            presentations: [ClaimPresentation::forValue(new Iban('EE471000001020145685'), ClaimEvidenceLevel::Verified)],
        );

        $this->claimRepository->expects($this->once())
            ->method('has')
            ->with($claimId)
            ->willReturn(true);

        $this->claimRepository->expects($this->once())
            ->method('load')
            ->with($claimId)
            ->willReturn($claim);

        $this->claimRepository->expects($this->never())->method('save');
        $this->commandBus->expects($this->never())->method('dispatch');

        ($this->handler)($command);
    }

    public function testResolveClaimNotDispatchedWhenClaimIsNotResolvable(): void
    {
        $source = ClaimSource::forPayment(PaymentId::fromString('018e1234-0000-7000-8000-000000000004'));
        $claimId = ClaimId::generateDeterministic($source);
        $command = new PresentClaimEvidence(
            source: $source,
            presentations: [
                ClaimPresentation::forValue(new Email('jane@example.com'), ClaimEvidenceLevel::Observed),
            ],
        );

        $this->claimRepository->expects($this->once())
            ->method('has')
            ->with($claimId)
            ->willReturn(false);

        /** @var ?Claim $savedClaim */
        $savedClaim = null;
        $this->claimRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Claim $claim) use (&$savedClaim): void {
                $savedClaim = $claim;
            });

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        ($this->handler)($command);

        self::assertInstanceOf(Claim::class, $savedClaim);
        self::assertNull($savedClaim->email());
        self::assertFalse($savedClaim->isResolvable());
    }
}
