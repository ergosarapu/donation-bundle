<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelRepositoryWrapperTrait;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;

final class PatchlevelPaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function save(PaymentMethod $paymentMethod): void
    {
        $this->saveAggregate($paymentMethod);
    }

    public function load(PaymentMethodId $paymentMethodId): PaymentMethod
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->loadAggregate($paymentMethodId);
        return $paymentMethod;
    }

    public function has(PaymentMethodId $paymentMethodId): bool
    {
        return $this->hasAggregate($paymentMethodId);
    }
}
