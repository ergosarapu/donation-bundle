<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Payum\Core\Payum;
use Payum\Core\Request\Notify;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentDoneController extends AbstractController
{
    public function __invoke(Request $request, Payum $payum): Response
    {
        $token = $payum->getHttpRequestVerifier()->verify($request);
        $gateway = $payum->getGateway($token->getGatewayName());
        $gateway->execute(new Notify($token));
        return $this->redirectToRoute('donation_thank_you');
    }
}
