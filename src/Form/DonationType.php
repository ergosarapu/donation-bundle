<?php

namespace ErgoSarapu\DonationBundle\Form;

use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Enum\DonationInterval;
use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder = new DynamicFormBuilder($builder);
        $builder
            ->add('type', EnumType::class, ['class' => DonationInterval::class])
            ->add('amount', MoneyType::class, ['divisor' => 100])
            ->add('chosenAmount', ChoiceType::class, 
                [
                    'choices' => [
                        MoneyDto::fromAmount('100'),
                        MoneyDto::fromAmount('250'),
                        MoneyDto::fromAmount('500'),
                        MoneyDto::fromAmount('1000'),
                        MoneyDto::fromAmount('2500'),
                    ],
                    'choice_value' => 'amount',
                    'choice_label' => function (MoneyDto $moneyDto): string {
                        $currencies = new ISOCurrencies();
                        $numberFormatter = new \NumberFormatter('et_EE', \NumberFormatter::CURRENCY);
                        $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
                        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
                        return $moneyFormatter->format($moneyDto->toMoney());
                    }
                ])
            ->add('taxReturn', CheckboxType::class, ['required' => false])
            ->addDependent('givenName', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class);
                }
            })
            ->addDependent('familyName', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class);
                }
            })
            ->addDependent('nationalIdCode', ['taxReturn'], function(DependentField $field, bool $taxReturn){
                if ($taxReturn === true){
                    $field->add(TextType::class);
                }
            })
            ->add('paymentCountry', ChoiceType::class, ['choices' => $options['payment_methods']['countries']])
            ->addDependent('paymentMethod', ['paymentCountry'], function(DependentField $field, ?string $paymentCountry) use ($options) {
                if ($paymentCountry === null) {
                    $paymentCountry = reset($options['payment_methods']['countries']);
                }
                if (!isset($options['payment_methods']['methods'][$paymentCountry])) {
                    return;
                }
                $field->add(ChoiceType::class, ['choices' => $options['payment_methods']['methods'][$paymentCountry], 'required' => true, 'placeholder' => 'Choose payment method']);
            })
            ->add('submit', SubmitType::class);
        
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DonationDto::class,
        ]);

        $resolver->setRequired(['payment_methods']);

        $resolver->setAllowedTypes('payment_methods', 'array');

        $resolver->setNormalizer('payment_methods', function (Options $options, array $paymentMethods): array {
            $countries = [];
            $countryMethods = [];
            
            foreach($paymentMethods as $method => $methodConfig) {
                $countryCode = $methodConfig['country_code'];
                if (!isset($countryMethods[$countryCode])) {
                    $countryMethods[$countryCode] = [];
                }
                $countryMethods[$countryCode][$methodConfig['label']] = $method;
                $countries[Countries::getName($countryCode)] = $countryCode;
            }

            return ['countries' => $countries, 'methods' => $countryMethods];
        });
    }
}
