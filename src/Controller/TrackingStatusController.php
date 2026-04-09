<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\GetTrackingStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\TrackingStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TrackingStatusController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {
    }

    public function __invoke(string $trackingId): Response
    {
        /** @var TrackingStatus $trackingStatus */
        $trackingStatus = $this->queryBus->ask(new GetTrackingStatus($trackingId));

        if ($trackingStatus === null) {
            // If tracking status is not yet available, return 202 Accepted to indicate
            // status is not available yet and client should retry later
            return new JsonResponse(status: Response::HTTP_ACCEPTED);
        }

        $data = array_filter([
            'paymentId' => $trackingStatus->getPaymentId(),
            'paymentMethodId' => $trackingStatus->getPaymentMethodId(),
            'updatedAt' => $trackingStatus->getUpdatedAt()->format('c'),
        ], fn ($value) => $value !== null);

        return new JsonResponse($data);
    }

}
