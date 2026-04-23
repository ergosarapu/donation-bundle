<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentImportDecoderInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;

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

        $commandResults = array_map(
            fn ($c) => $this->commandBus->dispatch($c),
            $commands
        );

        $pendingPaymentIds = array_map(
            fn ($result) => $result->result,
            $commandResults,
        );
        $pendingPaymentIds = array_filter(
            $pendingPaymentIds,
            fn ($id) => $id instanceof PaymentId
        );

        $skipped = count($commandResults) - count($pendingPaymentIds);

        return new PaymentFileImportResult(array_values($pendingPaymentIds), $skipped);
    }
}
