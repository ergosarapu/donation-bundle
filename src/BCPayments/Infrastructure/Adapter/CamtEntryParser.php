<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Iban;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Genkgo\Camt\DTO\Creditor;
use Genkgo\Camt\DTO\CreditorAgent;
use Genkgo\Camt\DTO\Debtor;
use Genkgo\Camt\DTO\DebtorAgent;
use Genkgo\Camt\DTO\Entry;
use Genkgo\Camt\DTO\EntryTransactionDetail;
use Genkgo\Camt\DTO\OrganisationIdentification;
use Genkgo\Camt\DTO\PrivateIdentification;
use InvalidArgumentException;

class CamtEntryParser
{
    private readonly EntryTransactionDetail $txDetail;

    private const DEBIT_INDICATOR = 'DBIT';
    private const CREDIT_INDICATOR = 'CRDT';

    /**
     * @var class-string<Creditor|Debtor> $counterPartyType
     */
    private readonly string $counterPartyType;

    /**
     * @var class-string<CreditorAgent|DebtorAgent> $counterPartyAgentType
     */
    private readonly string $counterPartyAgentType;

    public function __construct(private readonly Entry $entry)
    {
        $this->txDetail = $this->getTxDetail($entry);
        $this->counterPartyType =  $this->getCounterPartyType();
        $this->counterPartyAgentType = $this->getCounterPartyAgentType($this->counterPartyType);
    }

    public function isSettled(): bool
    {
        return $this->entry->getStatus() === 'BOOK';
    }

    /**
     * @return class-string<Creditor|Debtor>
     */
    private function getCounterPartyType(): string
    {
        $creditDebitIndicator = $this->entry->getCreditDebitIndicator();
        if ($creditDebitIndicator === self::DEBIT_INDICATOR) {
            return Creditor::class;
        }
        if ($creditDebitIndicator === self::CREDIT_INDICATOR) {
            return Debtor::class;
        }
        throw new InvalidArgumentException('Invalid Credit/Debit indicator value for entry: ' . $creditDebitIndicator);
    }

    /**
     * @param class-string<Creditor|Debtor> $counterPartyType
     * @return class-string<CreditorAgent|DebtorAgent>
     */
    private function getCounterPartyAgentType(string $counterPartyType): string
    {
        if ($counterPartyType === Creditor::class) {
            return CreditorAgent::class;
        }
        if ($counterPartyType === Debtor::class) {
            return DebtorAgent::class;
        }
        throw new InvalidArgumentException('Could not resolve agent type for: ' . $counterPartyType);
    }

    public function getBankReference(): BankReference
    {
        $accountServicerReference = $this->txDetail->getReference()?->getAccountServicerReference();
        if ($accountServicerReference === null) {
            // Fall back to entry level reference if transaction detail reference is not available
            $accountServicerReference = $this->entry->getAccountServicerReference();
        }
        if (null === $accountServicerReference) {
            throw new \InvalidArgumentException('Account servicer reference not found for entry');
        }
        return new BankReference($accountServicerReference);
    }

    private function getTxDetail(Entry $entry): EntryTransactionDetail
    {
        if (count($entry->getTransactionDetails()) > 1) {
            throw new \InvalidArgumentException('Entry with multiple transaction details not supported');
        }
        $txDetail = $entry->getTransactionDetail();
        if (null === $txDetail) {
            throw new InvalidArgumentException('Transaction detail not found for entry');
        }
        return $txDetail;
    }

    public function getBookingDate(): DateTimeImmutable
    {
        $bookingDate = $this->entry->getBookingDate();
        if ($bookingDate === null) {
            throw new \InvalidArgumentException('Booking date not found for entry');
        }
        return $bookingDate;
    }

    public function getAmount(): Money
    {
        $amount = $this->txDetail->getAmount();
        if ($amount === null) {
            // Fall back to entry level amount if transaction detail amount is not available
            $amount = $this->entry->getAmount();
        }
        return new Money((int)$amount->getAmount(), new Currency($amount->getCurrency()->getCode()));
    }

    public function getDescription(): ?ShortDescription
    {
        $rmInfo = $this->txDetail->getRemittanceInformation();
        if (null === $rmInfo) {
            return null;
        }

        $usBlock = $rmInfo->getUnstructuredBlock();
        if (null === $usBlock) {
            return null;
        }

        return new ShortDescription($usBlock->getMessage());
    }

    public function getAccountHolderName(): ?AccountHolderName
    {
        $parties = $this->txDetail->getRelatedParties();

        foreach ($parties as $party) {
            $type = $party->getRelatedPartyType();
            if (is_a($type, $this->counterPartyType)) {
                $name = $type->getName();
                if (null === $name) {
                    continue;
                }
                return new AccountHolderName($name);
            }
        }

        return null;
    }

    public function getNationalIdCode(): ?NationalIdCode
    {
        $parties = $this->txDetail->getRelatedParties();

        foreach ($parties as $party) {
            $type = $party->getRelatedPartyType();
            if (is_a($type, $this->counterPartyType)) {
                $identification = $type->getIdentification();
                if ($identification instanceof PrivateIdentification) {
                    if ('NIDN' == $identification->getOtherSchemeName()) {
                        $idCode = $identification->getOtherId();
                        if (null === $idCode) {
                            continue;
                        }
                        return new NationalIdCode($idCode);
                    }
                }
            }
        }

        return null;
    }

    public function getOrganisationRegCode(): ?OrganisationRegCode
    {
        $parties = $this->txDetail->getRelatedParties();

        foreach ($parties as $party) {
            $type = $party->getRelatedPartyType();
            if (is_a($type, $this->counterPartyType)) {
                $identification = $type->getIdentification();
                if ($identification instanceof OrganisationIdentification) {
                    $regCode = $identification->getOtherId();
                    if (null === $regCode) {
                        continue;
                    }
                    return new OrganisationRegCode($regCode);
                }
            }
        }

        return null;
    }

    public function getPaymentReference(): ?PaymentReference
    {
        $rmInfo = $this->txDetail->getRemittanceInformation();
        if (null === $rmInfo) {
            return null;
        }

        $strBlock = $rmInfo->getStructuredBlock();
        if (null === $strBlock) {
            return null;
        }

        $cdtrRefInfo = $strBlock->getCreditorReferenceInformation();
        if (null === $cdtrRefInfo) {
            return null;
        }

        if ('SCOR' === $cdtrRefInfo->getCode()) {
            $scor = $cdtrRefInfo->getRef();
            if (null === $scor) {
                return null;
            }
            return new PaymentReference($scor);
        }

        return null;
    }

    public function getIban(): ?Iban
    {
        $parties = $this->txDetail->getRelatedParties();

        foreach ($parties as $party) {
            $type = $party->getRelatedPartyType();
            if (is_a($type, $this->counterPartyType)) {
                $iban = $party->getAccount()?->getIdentification();
                if (null === $iban) {
                    return null;
                }
                return new Iban($iban);
            }
        }

        return null;
    }

    public function getBic(): ?Bic
    {
        $agents = $this->txDetail->getRelatedAgents();

        foreach ($agents as $agent) {
            $type = $agent->getRelatedAgentType();
            if (is_a($type, $this->counterPartyAgentType)) {
                return new Bic($type->getBIC());
            }
        }

        return null;
    }
}
