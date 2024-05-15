<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDonationController extends AbstractController
{

    public function handle(Request $request): Response
    {
        $donation = new DonationDto();

        $form = $this->createForm(DonationType::class, $donation);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $donation = $form->getData();

            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('task_success');
        }

        return $this->render($this->getTemplate(), [
            'form' => $form,
            'donation' => $donation
        ]);
    }

    public abstract function getTemplate():string;
}
