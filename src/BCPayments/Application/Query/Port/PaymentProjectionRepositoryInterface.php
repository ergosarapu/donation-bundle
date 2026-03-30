<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;

interface PaymentProjectionRepositoryInterface
{
    public function findOne(?PaymentId $id = null, ?PaymentStatus $status = null): ?Payment;

    public function countBy(?PaymentImportStatus $importStatus = null): int;
}
