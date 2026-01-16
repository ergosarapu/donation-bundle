<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

interface GatewayCaptureResult
{
    public function isSuccess(): bool;

    public function isTransientFailure(): bool;

    public function getCapturedAmount(): Money;

    public function getPaymentMethodResult(): ?PaymentMethodResult;
}
