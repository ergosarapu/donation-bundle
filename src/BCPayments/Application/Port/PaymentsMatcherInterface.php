<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

interface PaymentsMatcherInterface
{
    /**
     * @return array<PaymentMatch>
     */
    public function match(PaymentId $paymentId): array;
}
