<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use LogicException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class PaymentMethodActionTest extends TestCase
{
    public function testGetCreateForReturnsCreateForOnRequestIntent(): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $paymentId = PaymentId::generate();
        $createFor = Uuid::uuid7()->toString();

        $action = PaymentMethodAction::forRequest($paymentMethodId, $paymentId, $createFor);

        $this->assertSame($createFor, $action->getCreateFor());
    }

    public function testGetCreateForThrowsOnUseIntent(): void
    {
        $action = PaymentMethodAction::forUse(PaymentMethodId::generate(), PaymentId::generate());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('CreateFor is only available for request intent.');

        $action->getCreateFor();
    }
}
