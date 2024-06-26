<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Money\Money;
use Payum\Core\Payum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractPaymentController
{

    public function __invoke(Request $request): Response
    {
        $donation = new DonationDto();

        // Set initial default value
        $donation->setAmount(MoneyDto::fromMoney(Money::EUR(2500)));
        
        $form = $this->createForm(DonationType::class, $donation, ['payments_config' => $this->paymentsConfig]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DonationDto $donation */
            $donation = $form->getData();

            $gatewayName = $donation->getGateway();
            $storage = $this->payum->getStorage(Payment::class);
            
            /** @var Payment $payment */
            $payment = $storage->create();
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
            
            $storage->update($payment);
                        
            $captureToken = $this->payum->getTokenFactory()->createCaptureToken(
                $gatewayName, 
                $payment,
                'payment_done' // the route to redirect after capture
            );
            
            return $this->redirect($captureToken->getTargetUrl());    

        }


        return $this->render('@Donation/landing.html.twig', [
            'form' => $form,
            'donation' => $donation
        ]);
    }
}
