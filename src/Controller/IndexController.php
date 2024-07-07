<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Form\DonationType;
use ErgoSarapu\DonationBundle\Payum\PayumPaymentProvider;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{

    public function __construct(private PayumPaymentProvider $provider)
    {
    }

    public function __invoke(Request $request): Response
    {
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
            $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $this->campaignPublicId));
            $payment->setClientId(null);
            $payment->setClientEmail($donation->getEmail());
            $payment->setGivenName($donation->getGivenName());
            $payment->setFamilyName($donation->getFamilyName());
            $payment->setNationalIdCode($donation->getNationalIdCode());
            
            $this->provider->updatePayment($payment);
                        
            $targetUrl = $this->provider->createCaptureTargetUrl($gatewayName, $payment, 'payment_done');
            
            return $this->redirect($targetUrl);    

        }

        return $this->render('@Donation/landing.html.twig', [
            'form' => $form,
            'donation' => $donation
        ]);
    }
}
