<?php

namespace ErgoSarapu\DonationBundle\Tests\Helpers\Payum;

use ErgoSarapu\DonationBundle\Payum\Request\GetStandingAmount;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class TestStandingAmountAction implements ActionInterface
{
    public function execute($request): void{
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($model['amount'])) {
            return;
        }

        $amount = new Money($model['amount'] * 100, new Currency('EUR'));
        $request->setAmount($amount);
    }

    public function supports($request) {
        return
            $request instanceof GetStandingAmount &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}