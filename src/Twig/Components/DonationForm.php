<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Controller\AbstractPaymentController;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationType;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(template: '@Donation/components/donation_form.html.twig')]
final class DonationForm extends AbstractPaymentController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $initialFormData = null;

    protected function instantiateForm(): FormInterface {
        $payment = $this->initialFormData ?? new DonationDto();

        return $this->createForm(DonationType::class, $payment, ['payments_config' => $this->paymentsConfig]);
    }
}
