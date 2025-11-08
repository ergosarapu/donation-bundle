<?php

namespace ErgoSarapu\DonationBundle\Payum\Request;

use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Payum\Core\Request\Generic;

class GetStandingAmount extends Generic
{
    private ?Money $amount = null;

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount;
    }
}
