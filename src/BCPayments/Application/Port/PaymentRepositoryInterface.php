<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function load(PaymentId $paymentId): Payment;

    public function has(PaymentId $paymentId): bool;
}
