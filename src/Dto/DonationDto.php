<?php

namespace ErgoSarapu\DonationBundle\Dto;

use ErgoSarapu\DonationBundle\Enum\DonationInterval;

class DonationDto
{

    private DonationInterval $type = DonationInterval::Single;

    private ?string $givenName = null;

    private ?string $familyName = null;

    private ?string $nationalIdCode = null;

    private ?MoneyDto $amount = null;

    private ?MoneyDto $chosenAmount = null;

    private bool $taxReturn = false;

    public function getType():DonationInterval{
        return $this->type;
    }

    public function setType(DonationInterval $type):void{
        $this->type = $type;
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
}
