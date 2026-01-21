<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;

interface PaymentMethodRepositoryInterface
{
    public function save(PaymentMethod $paymentMethod): void;

    public function load(PaymentMethodId $paymentMethodId): PaymentMethod;

    public function has(PaymentMethodId $paymentMethodId): bool;
}
