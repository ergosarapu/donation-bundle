<?php

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Payum\UpdatePaymentStatusExtension;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\GatewayInterface;
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

    protected function setUp(): void
    {
        $this->payment = new Payment();
        $this->payment->setStatus(Status::Created);
        $this->request = new Notify($this->payment);
        $this->request->setModel(new ArrayObject()); // Calling this will set the initial model as firstModel
        $this->gatewayMock = $this->createMock(GatewayInterface::class);
        $this->context = new Context($this->gatewayMock, $this->request, []);
        $this->extension = new UpdatePaymentStatusExtension();
    }

    public function testStatusCaptured() { 
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markCaptured();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Captured, $this->payment->getStatus());
    }

    public function testStatusAuthorized() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markAuthorized();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Authorized, $this->payment->getStatus());
    }

    public function testStatusCanceled() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markCanceled();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Canceled, $this->payment->getStatus());
    }

    public function testStatusExpired() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markExpired();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Expired, $this->payment->getStatus());
    }

    public function testStatusFailed() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markFailed();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Failed, $this->payment->getStatus());
    }

    public function testStatusNew() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markNew();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Created, $this->payment->getStatus());
    }

    public function testStatusPayedout() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markPayedout();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Paid, $this->payment->getStatus());
    }

    public function testStatusPending() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markPending();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Pending, $this->payment->getStatus());
    }

    public function testStatusRefunded() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markRefunded();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Refunded, $this->payment->getStatus());
    }

    public function testStatusSuspended() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markSuspended();
        });

        $this->expectException(ValueError::class);
        $this->extension->onPostExecute($this->context);
    }

    public function testStatusUnknown() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markUnknown();
        });
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Unknown, $this->payment->getStatus());
    }
}
