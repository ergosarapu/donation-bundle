<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\CommandStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\CommandStatusProjectionRepositoryInterface;

class CommandStatusProjectionRepository implements CommandStatusProjectionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $projectionEntityManager
    ) {
    }

    /**
     * @param array<string> $commandCorrelationIds
     * @return array<CommandStatus>
     */
    public function findBy(array $commandCorrelationIds = []): array
    {
        if (empty($commandCorrelationIds)) {
            return [];
        }

        return $this->projectionEntityManager
            ->getRepository(CommandStatus::class)
            ->findBy(['correlationId' => $commandCorrelationIds]);
    }
}
