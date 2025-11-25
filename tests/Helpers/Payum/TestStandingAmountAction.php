<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers\Payum;

use ErgoSarapu\DonationBundle\Payum\Request\GetStandingAmount;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\RequestNotSupportedException;

class TestStandingAmountAction implements ActionInterface
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);


        if (!$request instanceof GetStandingAmount) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($model['amount'])) {
            return;
        }

        $amountValue = $model['amount'];
        if (!is_numeric($amountValue)) {
            throw new InvalidArgumentException('Amount must be numeric');
        }
        $amount = new Money((int)($amountValue * 100), new Currency('EUR'));
        $request->setAmount($amount);
    }

    public function supports($request)
    {
        return
            $request instanceof GetStandingAmount &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
