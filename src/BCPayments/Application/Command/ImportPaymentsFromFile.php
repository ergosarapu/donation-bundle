<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Command;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;

final class ImportPaymentsFromFile implements CommandInterface
{
    public function __construct(
        public readonly string $fileName,
    ) {
    }
}
