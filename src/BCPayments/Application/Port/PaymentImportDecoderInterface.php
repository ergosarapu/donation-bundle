<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;

interface PaymentImportDecoderInterface
{
    /**
     * @return array<CreatePendingPaymentImport>
     */
    public function getCommands(string $fileName): array;

}
