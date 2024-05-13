<?php

namespace ErgoSarapu\DonationBundle\Entity;

class Payment
{

    private ?string $givenName = null;

    private ?string $familyName = null;

    private ?string $nationalIdCode = null;

    private ?int $amount = null;

    private bool $taxReturn = false;

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

    public function getAmount():?int{
        return $this->amount;
    }

    public function setAmount(?int $amount):void{
        $this->amount = $amount;
    }

    public function isTaxReturn():bool{
        return $this->taxReturn;
    }

    public function setTaxReturn(bool $taxReturn):void{
        $this->taxReturn = $taxReturn;
    }
}
