<?php

namespace ErgoSarapu\DonationBundle\Traits;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

trait DonationFormTrait
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $initialFormData = null;

    protected function instantiateForm(): FormInterface {
        $payment = $this->initialFormData ?? new DonationDto();

        return $this->createForm(DonationType::class, $payment);     
    }
}
