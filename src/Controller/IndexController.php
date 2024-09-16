<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Form\DonationType;
use ErgoSarapu\DonationBundle\Payum\PayumPaymentProvider;
use ErgoSarapu\DonationBundle\Repository\CampaignRepository;
use InvalidArgumentException;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{

    public function __construct(private PayumPaymentProvider $provider, private readonly CampaignRepository $campaignRepository)
    {
    }

    public function __invoke(Request $request): Response
    {
        $campaigns = $this->campaignRepository->findBy(['default' => true]);
        if (count($campaigns) === 0) {
            throw new InvalidArgumentException('No default campaign found');
        }
        if (count($campaigns) > 1) {
            throw new InvalidArgumentException('Multiple default campaigns found');
        }
        
        $campaign = $campaigns[0];
        $donation = new DonationDto();

        // Set initial default value
        $donation->setAmount(MoneyDto::fromMoney(Money::EUR(2500)));
        
        $form = $this->createForm(DonationType::class, $donation, ['payments_config' => $this->provider->getPaymentsConfig()]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DonationDto $donation */
            $donation = $form->getData();

            $gatewayName = $donation->getGateway();
            
            $payment = $this->provider->createPayment();
            $payment->setStatus(Status::Created);
            $payment->setNumber(uniqid());
            $payment->setCurrencyCode($donation->getAmount()->currency);
            $payment->setTotalAmount($donation->getAmount()->amount);
            $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $campaign->getPublicId()));
            $payment->setClientId(null);
            $payment->setClientEmail($donation->getEmail());
            $payment->setGivenName($donation->getGivenName());
            $payment->setFamilyName($donation->getFamilyName());
            $payment->setNationalIdCode($donation->getNationalIdCode());
            $payment->setCampaign($campaign);
            
            $this->provider->updatePayment($payment);
                        
            $targetUrl = $this->provider->createCaptureTargetUrl($gatewayName, $payment, 'payment_done');
            
            return $this->redirect($targetUrl);    

        }

        return $this->render('@Donation/landing.html.twig', [
            'form' => $form,
            'donation' => $donation,
            'campaign' => $campaign,
        ]);
    }
}
