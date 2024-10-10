<?php

namespace ErgoSarapu\DonationBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use TalesFromADev\FlowbiteBundle\Form\Type\SwitchType;

class DonationFormStep2Type extends AbstractDonationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('email', EmailType::class, ['label' => 'E-posti aadress'])
            ->add('taxReturn', SwitchType::class, ['required' => false, 'label' => 'Soovin tulumaksutagastust'])
            ->addDependent('givenName', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class, ['label' => 'Eesnimi']);
                }
            })
            ->addDependent('familyName', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class, ['label' => 'Perekonnanimi']);
                }
            })
            ->addDependent('nationalIdCode', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class, ['label' => 'Isikukood']);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('validation_groups', 'step2');

        // This prevents displaying the 'This form should not contain extra fields.' message when fields are getting hidden using Dependent Form Fields
        $resolver->setDefault('allow_extra_fields', true);
    }
}
