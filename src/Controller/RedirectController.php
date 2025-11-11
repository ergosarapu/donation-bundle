<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class RedirectController extends AbstractController
{
    /**
     * @param QueryBusInterface<mixed> $queryBus 
     */
    public function __construct(
        private readonly QueryBusInterface $queryBus)
    {
    }
    
    public function __invoke(?string $donationId = null, ?string $recurringDonationId = null): Response
    {
        // TODO: Recurring donations
        // if ($recurringDonationId !== null){
        //     return $this->handleRecurringDonationRedirection(RecurringDonationId::fromString($recurringDonationId));
        // }

        if ($donationId !== null){
            return $this->handleDonationRedirection(DonationId::fromString($donationId));
        }

        throw new BadRequestHttpException('Id must be provided');
    }

    private function handleDonationRedirection(DonationId $donationId): Response {
        $selfUrl = $this->generateUrl('donation_redirect', ['donationId' => $donationId->toString()]);
        /** @var ?Donation $donation  */
        $donation = $this->queryBus->ask(new GetDonation($donationId));
        if ($donation === null) {
            // Redirect to self after short delay to allow for projection to complete
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        if ($donation->getStatus() !== DonationStatus::Pending) {
            // Maybe redirect to home?
            throw new BadRequestHttpException('Donation is not pending');
        }
        return $this->handlePaymentRedirection($donation, $selfUrl);

    }

    private function handlePaymentRedirection(Donation $donation, string $selfUrl): Response
    {
        /** @var ?Payment $payment  */
        $payment = $this->queryBus->ask(new GetPayment(PaymentId::fromString($donation->getPaymentId())));
        if ($payment === null) {
            return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $selfUrl, 'redirectAfterMilliseconds' => 1000]);
        }
        if ($payment->getStatus() !== PaymentStatus::Pending) {
            // Maybe redirect to home?
            throw new BadRequestHttpException('Payment is not pending');
        }
        if ($payment->getRedirectUrl() === null) {
            throw new ServiceUnavailableHttpException('Payment capture URL not available');
        }
        return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $payment->getRedirectUrl(), 'redirectAfterMilliseconds' => 0]);
    }
}