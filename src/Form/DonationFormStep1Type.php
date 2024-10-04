<?php

namespace ErgoSarapu\DonationBundle\Form;

use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonationFormStep1Type extends AbstractDonationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder
            // ->add('type', EnumType::class, ['class' => DonationInterval::class])
            ->add('amount', MoneyType::class, ['divisor' => 100, 'label' => 'Sisesta annetuse summa'])
            ->get('amount')->addModelTransformer(
                new CallbackTransformer(
                    function (?MoneyDto $money):?string{
                        return $money?->amount;
                    },
                    function (?string $amount):MoneyDto{
                        if (!$amount){
                            return MoneyDto::fromAmount('0');
                        }
                        return MoneyDto::fromAmount($amount);
                    }
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('validation_groups', 'step1');
    }
}
