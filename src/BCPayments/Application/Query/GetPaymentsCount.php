<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\Query;

class GetPaymentsCount implements Query
{
    public function __construct(
        public readonly ?PaymentImportStatus $importStatus = null,
    ) {
    }
}
