<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RedirectController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {
    }

    public function __invoke(?string $donationId = null): Response
    {
        if ($donationId === null) {
            throw new BadRequestHttpException('Id must be provided');
        }

        return $this->handleDonationRedirection(DonationId::fromString($donationId));
    }

    private function handleDonationRedirection(DonationId $donationId): Response
    {
        $selfUrl = $this->generateUrl('donation_redirect', ['donationId' => $donationId->toString()]);
        /** @var ?Donation $donation  */
        $donation = $this->queryBus->ask(new GetDonation($donationId));
        if ($donation === null) {
            // Redirect to self after short delay to allow for projection to complete
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        if ($donation->getStatus() !== DonationStatus::Initiated) {
            // Maybe redirect to home?
            throw new BadRequestHttpException('Donation is not ' . DonationStatus::Initiated->value);
        }
        return $this->handlePaymentRedirection($donation, $selfUrl);

    }

    private function handlePaymentRedirection(Donation $donation, string $selfUrl): Response
    {
        /** @var ?Payment $payment  */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        if ($payment === null || $payment->getRedirectUrl() === null) {
            // Redirect to self after short delay to allow for projection to complete
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        if ($payment->getStatus() !== PaymentStatus::Initiated) {
            // Maybe redirect to home?
            throw new BadRequestHttpException('Payment is not ' . PaymentStatus::Initiated->value);
        }
        return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $payment->getRedirectUrl(), 'redirectAfterMilliseconds' => 0]);
    }
}
