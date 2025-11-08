<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Repository\PatchlevelRepositoryWrapperTrait;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

final class PatchlevelPaymentRepository implements PaymentRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(Payment $payment): void
    {
        $this->saveAggregate($payment);
    }

    public function load(PaymentId $paymentId): Payment
    {
        return $this->loadAggregate($paymentId);
    }

    public function has(PaymentId $paymentId): bool
    {
        return $this->hasAggregate($paymentId);
    }
}
