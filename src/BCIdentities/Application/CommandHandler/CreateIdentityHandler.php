<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\CreateIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Port\IdentityRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\Identity;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

final class CreateIdentityHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly IdentityRepositoryInterface $identityRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateIdentity $command): void
    {
        if ($this->identityRepository->has($command->identityId)) {
            return;
        }

        $identity = Identity::create($this->clock->now(), $command->identityId);

        try {
            $this->identityRepository->save($identity);
        } catch (AggregateAlreadyExistsException) {
        }
    }
}
