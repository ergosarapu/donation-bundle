<?php

namespace ErgoSarapu\DonationBundle\Traits;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

trait DonateFormTrait
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?Payment $initialFormData = null;

    protected function instantiateForm(): FormInterface {
        $payment = $this->initialFormData ?? new Payment();

        return $this->createForm(DonationType::class, $payment);     
    }
}
