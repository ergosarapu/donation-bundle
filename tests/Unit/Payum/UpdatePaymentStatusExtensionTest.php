<?php

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Payum\UpdatePaymentStatusExtension;
use Payum\Core\Extension\Context;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\ReplyInterface;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetStatusInterface;
use PHPUnit\Framework\TestCase;
use ValueError;

class UpdatePaymentStatusExtensionTest extends TestCase
{

    private Payment $payment; 

    private GetStatusInterface $request; 

    private Context $context;

    private UpdatePaymentStatusExtension $extension;

    protected function setUp(): void
    {
        $this->payment = new Payment();
        $this->payment->setStatus(Status::Created);
        $this->request = new GetHumanStatus($this->payment);
        $this->context = new Context(new FakeGateway(), $this->request, []);
        $this->extension = new UpdatePaymentStatusExtension();
    }

    public function testStatusCaptured() {
        $this->request->markCaptured();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Captured, $this->payment->getStatus());
    }

    public function testStatusAuthorized() {
        $this->request->markAuthorized();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Authorized, $this->payment->getStatus());
    }

    public function testStatusCanceled() {
        $this->request->markCanceled();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Canceled, $this->payment->getStatus());
    }

    public function testStatusExpired() {
        $this->request->markExpired();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Expired, $this->payment->getStatus());
    }

    public function testStatusFailed() {
        $this->request->markFailed();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Failed, $this->payment->getStatus());
    }

    public function testStatusNew() {
        $this->request->markNew();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Created, $this->payment->getStatus());
    }

    public function testStatusPayedout() {
        $this->request->markPayedout();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Paid, $this->payment->getStatus());
    }

    public function testStatusPending() {
        $this->request->markPending();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Pending, $this->payment->getStatus());
    }

    public function testStatusRefunded() {
        $this->request->markRefunded();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Refunded, $this->payment->getStatus());
    }

    public function testStatusSuspended() {
        $this->request->markSuspended();

        // We are not supporting recurring payments yet, this could change in future
        $this->expectException(ValueError::class);
        $this->extension->onPostExecute($this->context);
    }

    public function testStatusUnknown() {
        $this->request->markUnknown();
        $this->extension->onPostExecute($this->context);
        $this->assertEquals(Status::Unknown, $this->payment->getStatus());
    }
}

class FakeGateway implements GatewayInterface {

    public function execute($request, $catchReply = false): ?ReplyInterface{
        return null;
    }
}