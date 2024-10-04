<?php

namespace ErgoSarapu\DonationBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class DonationFormStep3Type extends AbstractDonationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder = new DynamicFormBuilder($builder);

        // Bank payment
        $choices = $this->getCountryChoices($this->getBankCountryCodes($options));
        $builder->add('bankCountry', ChoiceType::class, ['choices' => $choices, 'label' => 'Pangamakse riik']);
        $builder->addDependent('gateway', ['bankCountry'], function(DependentField $field, ?string $bankCountry) use ($options) {
            if ($bankCountry === null) {
                $bankCountry = $this->getDefaultBankCountry($options);
            }
            $gateways = $this->getBankGateways($options, $bankCountry);
            $field->add(ChoiceType::class, [
                'expanded' => true, 
                'required' => true, 
                'choices' => $gateways, 
                'placeholder' => 'Choose bank',
                'constraints' => [
                    new Choice(choices: array_values($gateways))
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('validation_groups', 'step3');
    }
    
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $countryCode = $this->getSelectedBankCountry($view, $options);
        foreach ($view['gateway']->children as $child){
            $child->vars['image'] = $this->getBankImage($countryCode, $child->vars['value'], $options);
        }
    }

    private function getBankImage(string $countryCode, string $gatewayName, array $options) : string {
        return $options['payments_config']['onetime']['bank'][$countryCode]['gateways'][$gatewayName]['image'];
    }

    private function getSelectedBankCountry(FormView $view, array $options) : string {
        $selected = $view['bankCountry']->vars['value'];
        if (empty($selected)) {
            $selected = $this->getDefaultBankCountry($options);
        }
        return $selected;
    }

    private function getDefaultBankCountry(array $options) : string {
        $countryCodes = $this->getBankCountryCodes($options);
        return reset($countryCodes);
    }
    
    private function getBankCountryCodes(array $options): array {
        return array_keys($options['payments_config']['onetime']['bank']);
    }

    private function getCountryChoices(array $countryCodes): array {
        $choicesWithLabels = [];
        foreach($countryCodes as $countryCode) {
            $choicesWithLabels[Countries::getName($countryCode)] = $countryCode;
        }
        return $choicesWithLabels;
    }
    
    private function getBankGateways(array $options, string $countryCode): array {
        $gateways = [];
        foreach ($options['payments_config']['onetime']['bank'][$countryCode]['gateways'] as $gatewayName => $gatewayProperties) {
            $gateways[$gatewayProperties['label']] = $gatewayName;
        }
        return $gateways;
    }

    
}
