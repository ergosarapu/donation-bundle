<?php

namespace ErgoSarapu\DonationBundle\Dto;

use ErgoSarapu\DonationBundle\Enum\DonationInterval;

class DonationDto
{

    private DonationInterval $type = DonationInterval::Single;

    private ?string $email = null;

    private ?string $givenName = null;

    private ?string $familyName = null;

    private ?string $nationalIdCode = null;

    private ?MoneyDto $amount = null;

    private ?MoneyDto $chosenAmount = null;

    private bool $taxReturn = false;

    private ?string $paymentCountry = null;

    private ?string $paymentMethod = null;

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

    public function getAmount():?MoneyDto{
        return $this->amount;
    }

    public function setAmount(?MoneyDto $amount):void{
        $this->amount = $amount;
    }

    public function getChosenAmount():?MoneyDto{
        return $this->chosenAmount;
    }

    public function setChosenAmount(?MoneyDto $chosenAmount):void{
        $this->chosenAmount = $chosenAmount;
    }

    public function isTaxReturn():bool{
        return $this->taxReturn;
    }

    public function setTaxReturn(bool $taxReturn):void{
        $this->taxReturn = $taxReturn;
    }

    public function getPaymentCountry():?string{
        return $this->paymentCountry;
    }

    public function setPaymentCountry(?string $paymentCountry):void{
        $this->paymentCountry = $paymentCountry;
    }

    public function getPaymentMethod():?string{
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod):void{
        $this->paymentMethod = $paymentMethod;
    }
}
