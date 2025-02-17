<?php

namespace ErgoSarapu\DonationBundle\Form;

class FormOptionsProvider
{
    public function __construct(private array $gatewaysConfig, private array $currenciesConfig)
    {
    }

    public function getGateways(?string $frequency = null): array {
        $filtered = array_filter($this->gatewaysConfig, function (array $gateway) use ($frequency) : bool {
            return in_array($frequency, $gateway['frequencies']);
        });
        
        array_walk($filtered, function(array &$gateway){
            unset($gateway['frequencies']);
        });
        
        return $filtered;
    }

    public function getFrequencies(): array {
        return array_reduce($this->gatewaysConfig, function (array $uniqueFrequencies, array $gateway): array {
            return array_unique(array_merge($uniqueFrequencies, $gateway['frequencies']));
        }, []);
    }

    public function getCurrencies(): array {
        return $this->currenciesConfig;
    }
}
