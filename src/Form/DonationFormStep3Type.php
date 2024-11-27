<?php

namespace ErgoSarapu\DonationBundle\Form;

use RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class DonationFormStep3Type extends AbstractDonationFormType
{

    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder = new DynamicFormBuilder($builder);

        // Group
        $builder->add('gatewayGroup', ChoiceType::class, [
            'choices' => $this->getGatewayGroupChoices($options['gateways']),
            'expanded' => true,
        ]);

        // Country
        $builder->addDependent('gatewayCountry', ['gatewayGroup'], function (DependentField $field, ?string $group) use ($options) {
            if ($group === null) {
                $group = $this->getDefaultGroupName($options['gateways']);
            }
            $countryCodes = $this->getGroupCountryCodes($group, $options['gateways']);
            $choices = $this->getCountryChoices($countryCodes);

            $field->add(ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
            ]);
        });

        $builder->addDependent('gateway', ['gatewayGroup', 'gatewayCountry'], function(DependentField $field, ?string $group, ?string $countryCode) use ($options) {
            if ($group === null) {
                $group = $this->getDefaultGroupName($options['gateways']);
            }
            if (empty($countryCode)) {
                $this->getGroupCountryCodes($group, $options['gateways']);
                $countryCode = $this->getDefaultGroupCountryCode($group, $options['gateways']);
            }

            $gateways = $this->getGatewayChoices($options['gateways'], $group, $countryCode);
            $field->add(ChoiceType::class, [
                'choices' => $gateways,
                'expanded' => true,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('validation_groups', 'step3');
        
        $resolver->define('frequency')->default(null)->info('Frequency type, null for one-time or dateinterval string, e.g. P1M for monthly');

        $resolver->define('gateways')->default(function (OptionsResolver $groupsResolver): void {
            $groupsResolver->setPrototype(true) // Marks gateway id
                ->define('group')->required()
                ->define('country')->default(null)
                ->define('label')->required()
                ->define('image')->required();
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Set first group as selected by default
        $hasChecked = array_reduce($view['gatewayGroup']->children, function (bool $carry, mixed $item): bool {
            return $carry || $item->vars['checked'];
        }, false);
        if (!$hasChecked) {
            // If nothing checked, check the first group
            $view['gatewayGroup']->children[0]->vars['checked'] = true;
        }

        // Set image for gateway options
        foreach ($view['gateway']->children as $child){
            $child->vars['image'] = $options['gateways'][$child->vars['value']]['image'];
        }
    }

    private function getGatewayGroupChoices(array $gateways): array {
        $choices = [];
        foreach($gateways as $gateway) {
            $group = $gateway['group'];
            $choices[$group] = $group;
        }
        return array_unique($choices);
    }

    private function getGroupCountryCodes(string $group, array $gateways): array {
        $countryCodes = [];
        foreach ($gateways as $gateway) {
            if ($group !== $gateway['group']){
                continue;
            }
            if ($gateway['country'] === null) {
                continue;
            }
            $countryCodes[] = $gateway['country'];
        }
        return array_unique($countryCodes);
    }

    private function getDefaultGroupCountryCode(string $group, array $gateways) : ?string {
        $countryCodes = $this->getGroupCountryCodes($group, $gateways);
        return array_shift($countryCodes);
    }
    
    private function getDefaultGroupName(array $gateways) : string {
        $first = array_shift($gateways);
        if ($first === null) {
            throw new RuntimeException('Empty gateways array provided');
        }
        return $first['group'];
    }

    private function getCountryChoices(array $countryCodes): ?array {
        $choicesWithLabels = [];
        foreach($countryCodes as $countryCode) {
            $choicesWithLabels[Countries::getName($countryCode)] = $countryCode;
        }
        return $choicesWithLabels;
    }

    private function filterGateways(array $gateways, ?string $group, ?string $countryCode): array {
        $filterFun = function ($gateway) use ($group, $countryCode) {
            return $gateway['group'] === $group && $gateway['country'] === $countryCode;
        };
        return array_filter($gateways, $filterFun);
    }

    private function getGatewayChoices(array $gateways, ?string $group, ?string $countryCode): array {
        $gateways = $this->filterGateways($gateways, $group, $countryCode);
        
        $choices = [];
        foreach ($gateways as $gatewayId => $gateway) {
            $choices[$gateway['label']] = $gatewayId;
        }
        return $choices;
    }
}
