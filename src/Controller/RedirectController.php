<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentByTrackingId;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {
    }

    public function __invoke(string $trackingId): Response
    {
        $selfUrl = $this->generateUrl('donation_redirect', ['trackingId' => $trackingId]);

        /** @var ?Payment $payment  */
        $payment = $this->queryBus->ask(new GetPaymentByTrackingId($trackingId));
        if ($payment === null) {
            // Payment not yet projected — poll
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        if ($payment->getStatus() !== PaymentStatus::Initiated) {
            // Payment not in initiated state — stop polling
            return $this->redirectToRoute('donation_thank_you');
        }
        if ($payment->getRedirectUrl() === null) {
            // Initiated but redirect URL not yet set — poll
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $payment->getRedirectUrl(), 'redirectAfterMilliseconds' => 0]);
    }
}
