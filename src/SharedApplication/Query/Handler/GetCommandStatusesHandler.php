<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedApplication\Query\Handler;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\GetCommandStatuses;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\CommandStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Port\CommandStatusProjectionRepositoryInterface;

class GetCommandStatusesHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly CommandStatusProjectionRepositoryInterface $repository
    ) {
    }

    /**
     * @return array<CommandStatus>
     */
    public function __invoke(GetCommandStatuses $query): array
    {
        return $this->repository->findBy($query->commandCorrelationIds);
    }
}
