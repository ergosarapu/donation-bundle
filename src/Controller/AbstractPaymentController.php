<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbstractPaymentController extends AbstractController
{
    protected array $paymentMethods;

    protected string $campaignPublicId;

    public function setPaymentMethods(array $paymentMethods){
        $this->paymentMethods = $paymentMethods;
    }

    public function setCampaignPublicId(string $campaignPublicId){
        $this->campaignPublicId = $campaignPublicId;
    }
}
