<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class PaymentFileImportResult
{
    /**
     * @param array<PaymentId> $pendingPaymentIds
     */
    public function __construct(
        public readonly array $pendingPaymentIds,
        public readonly int $skippedCount,
    ) {
    }

}
