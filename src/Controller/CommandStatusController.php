<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\GetCommandStatuses;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\CommandStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommandStatusController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $cmdCorIds = $request->query->all('ids');

        if (!is_array($cmdCorIds)) {
            $cmdCorIds = [];
        }

        $commandStatuses = $this->queryBus->ask(new GetCommandStatuses($cmdCorIds));

        if (!is_array($commandStatuses)) {
            return new JsonResponse(['error' => 'Invalid response from query bus'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = array_map(function (CommandStatus $status): array {
            return [
                'correlationId' => $status->correlationId,
            ];
        }, $commandStatuses);

        return new JsonResponse($data);
    }

}
