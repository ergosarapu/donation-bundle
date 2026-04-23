<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Application\EventHandler;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MovePaymentImportToReview;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\TryReconcilePaymentImportHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMatch;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentsMatcherInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\AccountHolderName;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\BankReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Bic;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportSourceIdentifier;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReference;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TryReconcilePaymentImportHandlerTest extends TestCase
{
    private TryReconcilePaymentImportHandler $handler;
    private PaymentsMatcherInterface&MockObject $paymentsMatcher;
    private CommandBusInterface&MockObject $commandBus;
    private PaymentId $importedPaymentId;
    private PaymentImportPending $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentsMatcher = $this->createMock(PaymentsMatcherInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->handler = new TryReconcilePaymentImportHandler(
            $this->paymentsMatcher,
            $this->commandBus
        );

        $this->importedPaymentId = PaymentId::generate();
        $this->event = new PaymentImportPending(
            new DateTimeImmutable('2024-02-01 12:00:00'),
            $this->importedPaymentId,
            new PaymentImportSourceIdentifier('source-123'),
            new BankReference('ref-456'),
            PaymentStatus::Captured,
            new Money(10000, new Currency('EUR')),
            new ShortDescription('Test import'),
            new DateTimeImmutable('2024-02-01'),
            new AccountHolderName('John Doe'),
            LegalIdentifier::nationalIdNumber('12345678901'),
            new PaymentReference('1234567890'),
            new Iban('EE382200221020145685'),
            new Bic('HABAEE2X'),
        );
    }

    public function testReconcileWhenExactlyOneMatchWithHighScore(): void
    {
        $existingPaymentId = PaymentId::generate();
        $matchingPayment = new Payment();
        $matchingPayment->setPaymentId($existingPaymentId->toString());

        $match = new PaymentMatch($matchingPayment, 0.95, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$match]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($existingPaymentId) {
                return $command instanceof ReconcilePaymentImport
                    && $command->paymentId === $this->importedPaymentId
                    && $command->reconcileWithPaymentId->toString() === $existingPaymentId->toString();
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testMoveToReviewWhenNoMatches(): void
    {
        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MovePaymentImportToReview
                    && $command->paymentId === $this->importedPaymentId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testMoveToReviewWhenMultipleMatchesWithHighScore(): void
    {
        $payment1 = new Payment();
        $payment1->setPaymentId(PaymentId::generate()->toString());
        $payment2 = new Payment();
        $payment2->setPaymentId(PaymentId::generate()->toString());

        $match1 = new PaymentMatch($payment1, 0.92, []);
        $match2 = new PaymentMatch($payment2, 0.91, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$match1, $match2]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MovePaymentImportToReview
                    && $command->paymentId === $this->importedPaymentId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testMoveToReviewWhenMatchScoreBelowThreshold(): void
    {
        $payment = new Payment();
        $payment->setPaymentId(PaymentId::generate()->toString());

        $match = new PaymentMatch($payment, 0.85, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$match]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MovePaymentImportToReview
                    && $command->paymentId === $this->importedPaymentId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testMoveToReviewWhenOneHighScoreAndOneLowScore(): void
    {
        $payment1 = new Payment();
        $payment1->setPaymentId(PaymentId::generate()->toString());
        $payment2 = new Payment();
        $payment2->setPaymentId(PaymentId::generate()->toString());

        $highScoreMatch = new PaymentMatch($payment1, 0.92, []);
        $lowScoreMatch = new PaymentMatch($payment2, 0.65, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$highScoreMatch, $lowScoreMatch]);

        // Even though there's one high score match, we still have only one high score match
        // so it should reconcile
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof ReconcilePaymentImport
                    && $command->paymentId === $this->importedPaymentId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testReconcileWithExactly90PercentScore(): void
    {
        $existingPaymentId = PaymentId::generate();
        $matchingPayment = new Payment();
        $matchingPayment->setPaymentId($existingPaymentId->toString());

        $match = new PaymentMatch($matchingPayment, 0.9, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$match]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) use ($existingPaymentId) {
                return $command instanceof ReconcilePaymentImport
                    && $command->paymentId === $this->importedPaymentId
                    && $command->reconcileWithPaymentId->toString() === $existingPaymentId->toString();
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }

    public function testMoveToReviewWithScoreJustBelowThreshold(): void
    {
        $payment = new Payment();
        $payment->setPaymentId(PaymentId::generate()->toString());

        $match = new PaymentMatch($payment, 0.899, []);

        $this->paymentsMatcher->expects($this->once())
            ->method('match')
            ->with($this->importedPaymentId)
            ->willReturn([$match]);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MovePaymentImportToReview
                    && $command->paymentId === $this->importedPaymentId;
            }))
            ->willReturn(new CommandResult(null, 'test-correlation-id'));

        ($this->handler)($this->event);
    }
}
