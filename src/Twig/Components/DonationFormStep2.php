<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationFormStep2Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DonationFormStep2 extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $donationData = null;

    #[LiveProp]
    public ?string $action = null;

    protected function instantiateForm(): FormInterface {
       return $this->createForm(DonationFormStep2Type::class, $this->donationData, ['action' => $this->action]);
    }
}
