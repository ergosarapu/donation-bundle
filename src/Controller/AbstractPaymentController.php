<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbstractPaymentController extends AbstractController
{
    public function __construct(
        protected array $paymentsConfig,
        protected string $campaignPublicId,
        protected Payum $payum)
    {
    }
}
