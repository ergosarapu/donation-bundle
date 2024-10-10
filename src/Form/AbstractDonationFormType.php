<?php

namespace ErgoSarapu\DonationBundle\Form;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractDonationFormType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DonationDto::class,
        ]);
    }

}
