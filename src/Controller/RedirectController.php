<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends AbstractController
{
    public function __invoke(string $targetUrl): Response
    {
        return $this->render('@Donation/redirect.html.twig', ['targetUrl' => $targetUrl]);
    }
}
