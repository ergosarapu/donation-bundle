<?php

namespace ErgoSarapu\DonationBundle\Form;

class FormOptionsProvider
{
    public function __construct(private ?array $paymentsOptions, private ?array $currenciesOptions)
    {
    }

    public function getPaymentsOptions(): ?array {
        return $this->paymentsOptions;
    }

    public function getCurrenciesOptions(): ?array {
        return $this->currenciesOptions;
    }
}
