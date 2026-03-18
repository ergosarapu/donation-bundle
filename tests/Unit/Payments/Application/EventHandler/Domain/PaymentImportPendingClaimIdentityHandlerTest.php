<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\EventHandler\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentImportPendingClaimIdentityHandler;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Iban;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Command\ClaimIdentityIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentImportPendingClaimIdentityHandlerTest extends TestCase
{
    private PaymentImportPendingClaimIdentityHandler $handler;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new PaymentImportPendingClaimIdentityHandler($this->commandBus);
    }

    public function testDispatchesClaimIdentityIntegrationCommandWithAllFields(): void
    {
        $paymentId = PaymentId::generate();
        $iban = new Iban('EE382200221020145685');
        $nationalIdCode = new NationalIdCode('38001085718');

        $event = new PaymentImportPending(
            new DateTimeImmutable('2024-02-01 12:00:00'),
            $paymentId,
            new PaymentImportSourceIdentifier('source-123'),
            new BankReference('ref-456'),
            PaymentStatus::Captured,
            new Money(10000, new Currency('EUR')),
            new ShortDescription('Test import'),
            new DateTimeImmutable('2024-02-01'),
            new AccountHolderName('John Doe'),
            $nationalIdCode,
            new OrganisationRegCode('12345678'),
            new PaymentReference('1234567890'),
            $iban,
            new Bic('HABAEE2X'),
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentId, $iban, $nationalIdCode) {
                if (!$command instanceof ClaimIdentityIntegrationCommand) {
                    return false;
                }
                return $command->claimId === $paymentId->toString()
                    && $command->source === 'Payments'
                    && $command->name === 'John Doe'
                    && $command->email === null
                    && $command->iban !== null
                    && $command->iban->value === $iban->value
                    && $command->nationalIdCode === $nationalIdCode;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($event);
    }

    public function testDispatchesClaimIdentityIntegrationCommandWithNullableFields(): void
    {
        $paymentId = PaymentId::generate();

        $event = new PaymentImportPending(
            new DateTimeImmutable('2024-02-01 12:00:00'),
            $paymentId,
            new PaymentImportSourceIdentifier('source-123'),
            new BankReference('ref-456'),
            PaymentStatus::Captured,
            new Money(10000, new Currency('EUR')),
            null,
            new DateTimeImmutable('2024-02-01'),
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($paymentId) {
                if (!$command instanceof ClaimIdentityIntegrationCommand) {
                    return false;
                }
                return $command->claimId === $paymentId->toString()
                    && $command->source === 'Payments'
                    && $command->name === null
                    && $command->email === null
                    && $command->iban === null
                    && $command->nationalIdCode === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($event);
    }
}
