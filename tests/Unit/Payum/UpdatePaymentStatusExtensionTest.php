<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payum;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Payum\Request\GetStandingAmount;
use ErgoSarapu\DonationBundle\Payum\UpdatePaymentStatusExtension;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueError;

class UpdatePaymentStatusExtensionTest extends TestCase
{
    private Payment $payment;

    private Notify $request;

    private Context $context;

    private UpdatePaymentStatusExtension $extension;

    private GatewayInterface&MockObject $gatewayMock;

    private CommandBusInterface&MockObject $commandBusMock;

    protected function setUp(): void
    {
        $this->payment = new Payment();
        $this->payment->setNumber(PaymentId::generate()->toString());
        $this->payment->setStatus(Status::Created);
        $this->request = new Notify($this->payment);
        $this->request->setModel(new ArrayObject()); // Calling this will set the initial model as firstModel
        $this->gatewayMock = $this->createMock(GatewayInterface::class);
        $this->commandBusMock = $this->createMock(CommandBusInterface::class);
        $this->context = new Context($this->gatewayMock, $this->request, []);
        $this->extension = new UpdatePaymentStatusExtension($this->commandBusMock);
    }

    public function testStatusCaptured(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markCaptured();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                $request->setAmount(new Money(100, new Currency('EUR')));
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsCaptured
                    && $command->paymentId->toString() === $this->payment->getNumber()
                    && $command->capturedAmount->amount() === 100
                    && $command->capturedAmount->currency()->code() === 'EUR';
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Captured, $this->payment->getStatus());
    }

    public function testStatusAuthorized(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markAuthorized();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                $request->setAmount(new Money(100, new Currency('EUR')));
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsAuthorized
                    && $command->paymentId->toString() === $this->payment->getNumber()
                    && $command->authorizedAmount->amount() === 100
                    && $command->authorizedAmount->currency()->code() === 'EUR';
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Authorized, $this->payment->getStatus());
    }

    public function testStatusCanceled(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markCanceled();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                // Don't set standing amount
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsCanceled
                    && $command->paymentId->toString() === $this->payment->getNumber();
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Canceled, $this->payment->getStatus());
    }

    public function testStatusExpired(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markExpired();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                // Don't set standing amount
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsFailed
                    && $command->paymentId->toString() === $this->payment->getNumber();
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Expired, $this->payment->getStatus());
    }

    public function testStatusFailed(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markFailed();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                // Don't set standing amount
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsFailed
                    && $command->paymentId->toString() === $this->payment->getNumber();
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Failed, $this->payment->getStatus());
    }

    public function testStatusNew(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markNew();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                // Don't set standing amount
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->never())->method('dispatch');
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Created, $this->payment->getStatus());
    }

    public function testStatusPayedout(): void
    {
        $this->gatewayMock->expects($this->exactly(1))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markPayedout();
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->expectException(ValueError::class);
        $this->extension->onPostExecute($this->context);
    }

    public function testStatusPending(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markPending();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                // Don't set standing amount
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Pending, $this->payment->getStatus());
    }

    public function testStatusRefunded(): void
    {
        $this->gatewayMock->expects($this->exactly(2))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markRefunded();
                return;
            }
            if ($callIndex === 2 && $request instanceof GetStandingAmount) {
                $request->setAmount(new Money(100, new Currency('EUR')));
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($command) {
                return $command instanceof MarkPaymentAsRefunded
                    && $command->paymentId->toString() === $this->payment->getNumber()
                    && $command->remainingAmount->amount() === 100
                    && $command->remainingAmount->currency()->code() === 'EUR';
            }));
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Refunded, $this->payment->getStatus());
    }

    public function testStatusSuspended(): void
    {
        $this->gatewayMock->expects($this->exactly(1))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markSuspended();
                return;
            }
            $this->fail('Unexpected invocation');
        });

        $this->expectException(ValueError::class);
        $this->extension->onPostExecute($this->context);
    }

    public function testStatusUnknown(): void
    {
        $this->gatewayMock->expects($this->exactly(1))->method('execute')->willReturnCallback(function (Generic $request) use (&$callIndex) {
            $callIndex++;
            if ($callIndex === 1 && $request instanceof GetHumanStatus) {
                $request->markUnknown();
                return;
            }
            $this->fail('Unexpected invocation');
        });
        $this->expectException(ValueError::class);
        $this->extension->onPostExecute($this->context);
    }
}
