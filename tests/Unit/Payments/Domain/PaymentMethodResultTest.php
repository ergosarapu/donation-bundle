<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use LogicException;
use PHPUnit\Framework\TestCase;

class PaymentMethodResultTest extends TestCase
{
    public function testValueThrowsWhenUnusable(): void
    {
        $result = PaymentMethodResult::unusable(PaymentMethodUnusableReason::RequestFailed);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Payment method result has no value (is unusable).');
        $result->value();
    }
    public function testReasonThrowsWhenUsable(): void
    {
        $result = PaymentMethodResult::usable(new PaymentCredentialValue('test-value'));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Payment method result has value (is usable).');
        $result->unusableReason();
    }
}
