<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentImportDecoderInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use Genkgo\Camt\Config;
use Genkgo\Camt\Reader;

class CamtImportDecoder implements PaymentImportDecoderInterface
{
    public function getCommands(string $fileName): array
    {
        $reader = new Reader(Config::getDefault());
        $message = $reader->readFile($fileName);
        $statements = $message->getRecords();

        $result = [];
        foreach ($statements as $statement) {
            $sourceIdentifier = new PaymentImportSourceIdentifier('CAMT-'.$statement->getAccount()->getIdentification());
            $entries = $statement->getEntries();
            foreach ($entries as $entry) {
                $parser = new CamtEntryParser($entry);
                if ($parser->isSettled() === false) {
                    continue; // Skip non-settled entries
                }

                $command = new CreatePendingPaymentImport(
                    $sourceIdentifier,
                    $parser->getBankReference(),
                    PaymentStatus::Settled,
                    $parser->getAmount(),
                    $parser->getDescription(),
                    $parser->getBookingDate(),
                    $parser->getAccountHolderName(),
                    $parser->getLegalIdentifier(),
                    $parser->getPaymentReference(),
                    $parser->getIban(),
                    $parser->getBic(),
                );

                $result[] = $command;
            }
        }

        return $result;
    }

}
