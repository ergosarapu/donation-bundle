<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationFormStep1Type;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DonationFormStep1 extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?DonationDto $donationData = null;

    #[LiveProp]
    public ?string $action = null;

    private MoneyToLocalizedStringTransformer $moneyTransformer;

    private string $locale;

    public function __construct(private FormOptionsProvider $formOptionsProvider, RequestStack $requestStack){
        $this->locale = $requestStack->getCurrentRequest()->getLocale();
        $this->moneyTransformer = new MoneyToLocalizedStringTransformer(
            divisor: 100,
            input: 'integer',
            locale: $this->locale
        );
    }

    protected function instantiateForm(): FormInterface {
        return $this->createForm(
            DonationFormStep1Type::class,
            $this->donationData,
            [
                'action' => $this->action,
                'currencies' => $this->formOptionsProvider->getCurrenciesOptions(),
                'locale' => $this->locale,
            ]);
            
    }

    #[LiveAction]
    public function chooseAmount(): void
    {
        if (empty($this->formValues['chosenAmount'])){
            // Custom amount chosen
            $this->formValues['amount'] = '';
            return;
        }

        $this->formValues['amount'] = $this->toLocalizedInputValue(
            $this->formValues['chosenAmount'],
            'EUR',
            $this->locale
        );
    }

    #[LiveAction]
    public function writeAmount(): void
    {
        // Using the same transformer used by the MoneyField, it works little bit better than Money Parser
        try {
            $normalized = $this->moneyTransformer->reverseTransform($this->formValues['amount']);
        } catch (TransformationFailedException $e){
            // Unrecognized input, select custom amount choice
            $this->formValues['chosenAmount'] = '';
            return;
        }

        // Check if normalized value exists in choices
        $options = $this->formOptionsProvider->getCurrenciesOptions();
        if (!in_array($normalized, $options['EUR']['amount_choices'])){
            $this->formValues['chosenAmount'] = '';
            return;
        }

        $this->formValues['chosenAmount'] = $normalized;
    }

    private function getDataModelValue(): ?string
    {
        return 'on(input)|*';
    }

    private function toLocalizedInputValue(int $normalized, string $currencyCode, string $locale): string{
        $money = new Money($normalized, new Currency($currencyCode));
        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $numberFormatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, '');
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());
        return $moneyFormatter->format($money);
    }
}
