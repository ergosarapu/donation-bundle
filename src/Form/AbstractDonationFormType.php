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
            // This prevents displaying the 'This form should not contain extra fields.' message when fields are getting hidden using Dependent Form Fields
            'allow_extra_fields' => true,
        ]);

        $resolver->setDefault('payments_config', function (OptionsResolver $paymentsResolver): void {
            $paymentsResolver->setDefault('onetime', function (OptionsResolver $onetimeResolver): void {
                $this->resolveBank($onetimeResolver);
                $this->resolveCard($onetimeResolver);
            });
            $paymentsResolver->setDefault('monthly', null); // TODO
        });
        $resolver->setRequired(['payments_config']);
        $resolver->setAllowedTypes('payments_config', 'array');
    }

    private function resolveGateways(OptionsResolver $resolver): void{
        $resolver
            ->setRequired(['gateways'])
            ->setDefault('gateways', function (OptionsResolver $resolver): void {
                $resolver
                    ->setPrototype(true) // Marks gateway name
                    ->setRequired('label')
                    ->setDefault('image', '');
        });
    }

    private function resolveBank(OptionsResolver $resolver): void{
        $resolver->setDefault('bank', function (OptionsResolver $resolver): void {
            $resolver->setPrototype(true); // Marks country code
            $this->resolveGateways($resolver);
        });
    }

    private function resolveCard(OptionsResolver $resolver): void{
        $resolver->setDefault('card', function (OptionsResolver $resolver): void {
            $this->resolveGateways($resolver);
        });
    }
}
