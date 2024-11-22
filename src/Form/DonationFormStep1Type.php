<?php

namespace ErgoSarapu\DonationBundle\Form;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonationFormStep1Type extends AbstractDonationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $amountChoices = $this->getAmountChoices($options, 'EUR'); // TODO: Handle different currencies
        $builder
            ->add('frequency', ChoiceType::class, [
                'choices' => $this->getFrequenciesChoices($options['frequencies']),
                'expanded' => true,
            ])
            ->add('currencyCode', HiddenType::class) // TODO: Let user choose different currency
            // ->add('type', EnumType::class, ['class' => DonationInterval::class])
            ->add('chosenAmount', ChoiceType::class, [
                'expanded' => true, 
                'required' => false, 
                'choices' => $amountChoices,
                'placeholder' => 'Muu summa',
            ])
            ->add('amount', MoneyType::class, ['divisor' => 100, 'label' => 'Sisesta annetuse summa'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('validation_groups', 'step1');

        $resolver->setDefault('currencies', function (OptionsResolver $currenciesResolver): void {
            $currenciesResolver->setPrototype(true); // Marks currency code
            $currenciesResolver->setRequired('amount_default');
            $currenciesResolver->setAllowedTypes('amount_default', 'int');
            
            $currenciesResolver->setRequired('amount_choices');
            $currenciesResolver->setAllowedTypes('amount_choices', 'int[]');
        });

        // Null represents one-time payment frequency
        $resolver->setDefault('frequencies', [null]);

        $resolver->setRequired(['currencies']);
        $resolver->setAllowedTypes('currencies', 'array');

        $resolver->setRequired('locale');
        $resolver->setAllowedTypes('locale', 'string');
    }

    private function getAmountChoices(array $options, string $currencyCode): array {
        $choices = [];
        foreach ($options['currencies'][$currencyCode]['amount_choices'] as $amountChoice) {
            $label = $this->toLocalizedChoiceLabel($amountChoice, $currencyCode, $options['locale']);
            $choices[$label] = $amountChoice;
        }
        return $choices;
    }

    private function toLocalizedChoiceLabel(int $amount, string $currencyCode, string $locale): string {
        $money = new Money($amount, new Currency($currencyCode));
        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());
        return $moneyFormatter->format($money);
    }

    private function getFrequenciesChoices(array $frequencies): array {
        $choices = [];
        foreach($frequencies as $frequency) {
            // Create translatable message key, e.g. payment.frequency.P1M
            $choices['payment.frequency.'.$frequency] = $frequency;
        }
        return $choices;
    }
}
