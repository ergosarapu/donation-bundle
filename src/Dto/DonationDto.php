<?php

namespace ErgoSarapu\DonationBundle\Dto;

use ErgoSarapu\DonationBundle\Enum\DonationInterval;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\When;

class DonationDto
{
    private const IS_TAX_RETURN = 'this.isTaxReturn() == true';

    private DonationInterval $type = DonationInterval::Single;

    #[NotBlank(groups: ['step2'])]
    #[Email(groups: ['step2'])]
    private ?string $email = null;

    #[When(
        expression: self::IS_TAX_RETURN,
        constraints: [new NotBlank()],
        groups: ['step2']
    )]
    private ?string $givenName = null;

    #[When(
        expression: self::IS_TAX_RETURN,
        constraints: [new NotBlank()],
        groups: ['step2']
    )]
    private ?string $familyName = null;

    #[When(
        expression: self::IS_TAX_RETURN,
        constraints: [new NotBlank()],
        groups: ['step2']
    )]
    private ?string $nationalIdCode = null;

    #[NotBlank(groups: ['step1'])]
    private ?string $currencyCode = null;

    #[NotBlank(groups: ['step1'])]
    #[GreaterThan(0, groups: ['step1'])]
    private ?int $amount = null;

    private ?int $chosenAmount = null;

    private bool $taxReturn = false;

    private ?string $bankCountry = null;

    #[NotNull(message: 'Choose bank for payment', groups:['step3'])]
    private ?string $gateway = null;

    public function getType():DonationInterval{
        return $this->type;
    }

    public function setType(DonationInterval $type):void{
        $this->type = $type;
    }

    public function getEmail():?string{
        return $this->email;
    }

    public function setEmail(?string $email):void{
        $this->email = $email;
    }

    public function getGivenName():?string{
        return $this->givenName;
    }

    public function setGivenName(?string $givenName):void{
        $this->givenName = $givenName;
    }

    public function getFamilyName():?string{
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName):void{
        $this->familyName = $familyName;
    }

    public function getNationalIdCode():?string{
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode):void{
        $this->nationalIdCode = $nationalIdCode;
    }

    public function getCurrencyCode():?string{
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode):void{
        $this->currencyCode = $currencyCode;
    }

    public function getAmount():?int{
        return $this->amount;
    }

    public function setAmount(?int $amount):void{
        $this->amount = $amount;
    }

    public function getChosenAmount():?int{
        return $this->chosenAmount;
    }

    public function setChosenAmount(?int $chosenAmount):void{
        $this->chosenAmount = $chosenAmount;
    }

    public function isTaxReturn():bool{
        return $this->taxReturn;
    }

    public function setTaxReturn(bool $taxReturn):void{
        $this->taxReturn = $taxReturn;
    }

    public function getBankCountry():?string{
        return $this->bankCountry;
    }

    public function setBankCountry(?string $bankCountry):void{
        $this->bankCountry = $bankCountry;
    }


    public function getGateway():?string{
        return $this->gateway;
    }

    public function setGateway(?string $gateway):void{
        $this->gateway = $gateway;
    }
}
