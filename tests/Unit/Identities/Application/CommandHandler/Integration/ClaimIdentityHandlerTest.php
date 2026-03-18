<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Identities\Application\CommandHandler\Integration;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ClaimIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\CommandHandler\Integration\ClaimIdentityHandler;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\EntityClaim\EntityClaimSource;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Command\ClaimIdentityIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ClaimIdentityHandlerTest extends TestCase
{
    private ClaimIdentityHandler $handler;
    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->handler = new ClaimIdentityHandler($this->commandBus);
    }

    public function testDispatchesClaimIdentityCommand(): void
    {
        $claimId = Uuid::uuid7()->toString();
        $source = 'Payments';
        $name = 'John Doe';
        $email = new Email('john.doe@example.com');
        $iban = new Iban('EE382200221020145685');
        $nationalIdCode = new NationalIdCode('38001085718');

        $integrationCommand = new ClaimIdentityIntegrationCommand(
            $claimId,
            $source,
            $name,
            $email,
            $iban,
            $nationalIdCode,
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($claimId, $name, $email, $iban, $nationalIdCode) {
                if (!$command instanceof ClaimIdentity) {
                    return false;
                }
                return $command->entityClaimId->toString() === $claimId
                    && $command->source === EntityClaimSource::Payments
                    && $command->name === $name
                    && $command->email === $email
                    && $command->iban === $iban
                    && $command->nationalIdCode === $nationalIdCode;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }

    public function testDispatchesClaimIdentityCommandWithNullableFields(): void
    {
        $claimId = Uuid::uuid7()->toString();

        $integrationCommand = new ClaimIdentityIntegrationCommand(
            $claimId,
            'Payments',
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($claimId) {
                if (!$command instanceof ClaimIdentity) {
                    return false;
                }
                return $command->entityClaimId->toString() === $claimId
                    && $command->source === EntityClaimSource::Payments
                    && $command->name === null
                    && $command->email === null
                    && $command->iban === null
                    && $command->nationalIdCode === null;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($integrationCommand);
    }
}
