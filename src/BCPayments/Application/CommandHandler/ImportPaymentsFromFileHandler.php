<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentImportDecoderInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class ImportPaymentsFromFileHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentImportDecoderInterface $decoder,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(ImportPaymentsFromFile $command): PaymentFileImportResult
    {
        $commands = $this->decoder->getCommands($command->fileName);

        $results = array_map(
            fn ($c) => $this->commandBus->dispatch($c),
            $commands
        );

        $pendingPaymentIds = array_filter(
            $results,
            fn ($result) => $result instanceof PaymentId
        );

        $skipped = count($results) - count($pendingPaymentIds);

        return new PaymentFileImportResult(array_values($pendingPaymentIds), $skipped);
    }
}
