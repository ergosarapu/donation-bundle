<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbstractPaymentController extends AbstractController
{
    protected array $paymentsConfig;

    protected string $campaignPublicId;

    public function setPaymentsConfig(array $paymentsConfig){
        $this->paymentsConfig = $paymentsConfig;
    }

    public function setCampaignPublicId(string $campaignPublicId){
        $this->campaignPublicId = $campaignPublicId;
    }
}
