<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

use LogicException;

class PaymentMethodResult
{
    private function __construct(
        public readonly ?PaymentCredentialValue $value,
        public readonly ?PaymentMethodUnusableReason $unusableReason,
    ) {
    }

    public static function usable(PaymentCredentialValue $value): self
    {
        return new self($value, null);
    }

    public static function unusable(PaymentMethodUnusableReason $unusableReason): self
    {
        return new self(null, $unusableReason);
    }

    public function isUsable(): bool
    {
        return $this->value !== null;
    }

    public function value(): PaymentCredentialValue
    {
        if ($this->value === null) {
            throw new LogicException('Payment method result has no value (is unusable).');
        }
        return $this->value;
    }

    public function unusableReason(): PaymentMethodUnusableReason
    {
        if ($this->unusableReason === null) {
            throw new LogicException('Payment method result has value (is usable).');
        }
        return $this->unusableReason;
    }

}
