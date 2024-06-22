<?php

namespace ErgoSarapu\DonationBundle\Form;

use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use TalesFromADev\FlowbiteBundle\Form\Type\SwitchType;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder = new DynamicFormBuilder($builder);
        $builder
            // ->add('type', EnumType::class, ['class' => DonationInterval::class])
            ->add('amount', MoneyType::class, ['divisor' => 100, 'label' => 'Sisesta annetuse summa'])
            ->add('email', EmailType::class, ['label' => 'E-posti aadress'])
            // ->add('chosenAmount', ChoiceType::class, 
            //     [
            //         'choices' => [
            //             MoneyDto::fromAmount('100'),
            //             MoneyDto::fromAmount('250'),
            //             MoneyDto::fromAmount('500'),
            //             MoneyDto::fromAmount('1000'),
            //             MoneyDto::fromAmount('2500'),
            //         ],
            //         'choice_value' => 'amount',
            //         'choice_label' => function (MoneyDto $moneyDto): string {
            //             $currencies = new ISOCurrencies();
            //             $numberFormatter = new \NumberFormatter('et_EE', \NumberFormatter::CURRENCY);
            //             $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
            //             $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
            //             return $moneyFormatter->format($moneyDto->toMoney());
            //         }
            //     ])
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

        // Bank payment
        $choices = $this->getCountryChoices($this->getBankCountryCodes($options));
        $builder->add('bankCountry', ChoiceType::class, ['choices' => $choices, 'label' => false]);
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

        $builder->add('submit', SubmitType::class);
        
        $builder->get('amount')->addModelTransformer(
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
