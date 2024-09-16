<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationType;
use ErgoSarapu\DonationBundle\Payum\PayumPaymentProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DonationForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $initialFormData = null;

    public function __construct(private PayumPaymentProvider $provider)
    {
    }

    protected function instantiateForm(): FormInterface {
        $payment = $this->initialFormData ?? new DonationDto();

        return $this->createForm(DonationType::class, $payment, ['payments_config' => $this->provider->getPaymentsConfig()]);
    }
}
