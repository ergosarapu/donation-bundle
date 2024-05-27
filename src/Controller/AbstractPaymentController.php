<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbstractPaymentController extends AbstractController
{
    protected array $paymentMethods;

    public function setPaymentMethods(array $paymentMethods){
        $this->paymentMethods = $paymentMethods;
    }
}
