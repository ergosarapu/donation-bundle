<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationFormStep3Type;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DonationFormStep3 extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $donationData = null;

    #[LiveProp]
    public ?string $currentUrl = null;

    #[LiveProp]
    public ?string $previousUrl = null;

    public function __construct(private FormOptionsProvider $provider)
    {
    }

    protected function instantiateForm(): FormInterface {
        $frequency = $this->donationData->getFrequency();
        $options = [
            'frequency' => $frequency,
            'gateways' => $this->provider->getGateways($frequency),
        ];
       return $this->createForm(DonationFormStep3Type::class, $this->donationData, $options);
    }
}
