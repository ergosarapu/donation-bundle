<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDonationController extends AbstractController
{

    public function handle(Request $request): Response
    {
        $payment = new Payment();

        $form = $this->createForm(DonationType::class, $payment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payment = $form->getData();

            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('task_success');
        }

        return $this->render($this->getTemplate(), [
            'form' => $form,
            'payment' => $payment
        ]);
    }

    public abstract function getTemplate():string;
}
