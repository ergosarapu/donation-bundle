<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Payum\Request;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use Payum\Core\Request\Generic;

class GetPaymentMethodResult extends Generic
{
    private ?PaymentMethodResult $result = null;

    public function getResult(): ?PaymentMethodResult
    {
        return $this->result;
    }

    public function setResult(?PaymentMethodResult $result): void
    {
        $this->result = $result;
    }
}
