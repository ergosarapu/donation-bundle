<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identity\Application\CommandHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\CreateIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\CreateIdentityHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\IdentityId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class CreateIdentityHandlerTest extends TestCase
{
    private CreateIdentityHandler $handler;
    private IdentityRepositoryInterface&MockObject $identityRepository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identityRepository = $this->createMock(IdentityRepositoryInterface::class);
        $this->now = new DateTimeImmutable('2026-01-01 10:00:00');

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($this->now);

        $this->handler = new CreateIdentityHandler($this->identityRepository, $clock);
    }

    public function testCreatesIdentity(): void
    {
        $command = new CreateIdentity(IdentityId::generate());

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->with($command->identityId)
            ->willReturn(false);

        $this->identityRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Identity::class));

        ($this->handler)($command);
    }

    public function testCreatesIdentityWithProvidedId(): void
    {
        $identityId = IdentityId::generate();
        $command = new CreateIdentity($identityId);

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->with($identityId)
            ->willReturn(false);

        $this->identityRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Identity::class));

        ($this->handler)($command);
    }

    public function testIgnoresCommandWhenIdentityAlreadyExists(): void
    {
        $command = new CreateIdentity(IdentityId::generate());

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->with($command->identityId)
            ->willReturn(true);

        $this->identityRepository->expects($this->never())
            ->method('save');

        ($this->handler)($command);
    }

    public function testHandlesAggregateAlreadyExistsException(): void
    {
        $command = new CreateIdentity(IdentityId::generate());

        $this->identityRepository->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->identityRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new AggregateAlreadyExistsException('Identity already exists'));

        ($this->handler)($command);
    }
}
