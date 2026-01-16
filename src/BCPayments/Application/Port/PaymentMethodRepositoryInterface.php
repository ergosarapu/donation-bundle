<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodlId;

interface PaymentMethodRepositoryInterface
{
    public function save(PaymentMethod $paymentMethod): void;

    public function load(PaymentMethodlId $paymentMethodId): PaymentMethod;

    public function has(PaymentMethodlId $paymentMethodId): bool;
}
