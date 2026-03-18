<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identities\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ClaimIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\ClaimIdentityHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\EntityClaimRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class ClaimIdentityHandlerTest extends TestCase
{
    private ClaimIdentityHandler $handler;
    private EntityClaimRepositoryInterface&MockObject $entityClaimRepository;
    private IdentityRepositoryInterface&MockObject $identityRepository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityClaimRepository = $this->createMock(EntityClaimRepositoryInterface::class);
        $this->identityRepository = $this->createMock(IdentityRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2024-02-01 12:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new ClaimIdentityHandler(
            $this->entityClaimRepository,
            $this->identityRepository,
            $clock,
        );
    }

    public function testCreatesEntityClaimAndIdentity(): void
    {
        $entityClaimId = EntityClaimId::generate();

        $this->entityClaimRepository->expects($this->once())
            ->method('has')
            ->with($entityClaimId)
            ->willReturn(false);

        $this->identityRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Identity::class));

        $this->entityClaimRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EntityClaim::class));

        $command = new ClaimIdentity(
            $entityClaimId,
            EntityClaimSource::Payments,
            'John Doe',
            null,
            null,
            null,
        );

        ($this->handler)($command);
    }

    public function testSkipsIfEntityClaimAlreadyExists(): void
    {
        $entityClaimId = EntityClaimId::generate();

        $this->entityClaimRepository->expects($this->once())
            ->method('has')
            ->with($entityClaimId)
            ->willReturn(true);

        $this->entityClaimRepository->expects($this->never())->method('save');
        $this->identityRepository->expects($this->never())->method('save');

        $command = new ClaimIdentity(
            $entityClaimId,
            EntityClaimSource::Payments,
            null,
            null,
            null,
            null,
        );

        ($this->handler)($command);
    }
}
