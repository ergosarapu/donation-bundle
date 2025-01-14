<?php

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status as PaymentStatus;
use ErgoSarapu\DonationBundle\Entity\Subscription\Status as SubscriptionStatus;
use ErgoSarapu\DonationBundle\Entity\Subscription;
use ErgoSarapu\DonationBundle\Payum\ActivateSubscriptionExtension;
use Generator;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivateSubscriptionExtensionTest extends TestCase
{

    private Payment $payment; 

    private Notify $request; 

    private Context $context;

    private ActivateSubscriptionExtension $extension;

    private GatewayInterface&MockObject $gatewayMock;

    protected function setUp(): void
    {
        $this->payment = new Payment();
        $subscription = new Subscription();
        $this->payment->setSubscription($subscription);
        $subscription->setStatus(SubscriptionStatus::Created);
        $subscription->setInitialPayment($this->payment);
        $this->payment->setStatus(PaymentStatus::Created);
        $this->request = new Notify($this->payment);
        $this->request->setModel(new ArrayObject()); // Calling this will set the initial model as firstModel
        $this->gatewayMock = $this->createMock(GatewayInterface::class);
        $this->context = new Context($this->gatewayMock, $this->request, []);
        $this->extension = new ActivateSubscriptionExtension();
    }

    public function testSubscriptionActivated() {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback(function (GetHumanStatus $request) {
            $request->markCaptured();
        });
        $this->extension->onExecute($this->context);
        $this->assertEquals(SubscriptionStatus::Active, $this->payment->getSubscription()->getStatus());
    }

    #[DataProvider('ignoredSubscriptionStatuses')]
    public function testSubscriptionStatusNotChangedForNotCreatedSubscription(SubscriptionStatus $status) {
        $this->payment->getSubscription()->setStatus($status);
        $this->extension->onExecute($this->context);
        $this->assertEquals($status, $this->payment->getSubscription()->getStatus());
    }

    public static function ignoredSubscriptionStatuses(): Generator
    {
        $statuses = array_filter(SubscriptionStatus::cases(), fn($status) => $status !== SubscriptionStatus::Created);
        foreach ($statuses as $status) {
            yield [$status];
        }
    }

    #[DataProvider('ignoredPaymentStatusCallbacks')]
    public function testSubscriptionStatusNotChangedForNotCapturedPayment(callable $callback) {
        $this->gatewayMock->expects($this->once())->method('execute')->willReturnCallback($callback);
        $this->extension->onExecute($this->context);
        $this->assertEquals(SubscriptionStatus::Created, $this->payment->getSubscription()->getStatus());
    }

    public static function ignoredPaymentStatusCallbacks(): Generator
    {
        yield [fn(GetHumanStatus $request) => $request->markAuthorized()];
        yield [fn(GetHumanStatus $request) => $request->markCanceled()];
        yield [fn(GetHumanStatus $request) => $request->markExpired()];
        yield [fn(GetHumanStatus $request) => $request->markFailed()];
        yield [fn(GetHumanStatus $request) => $request->markNew()];
        yield [fn(GetHumanStatus $request) => $request->markPayedout()];
        yield [fn(GetHumanStatus $request) => $request->markPending()];
        yield [fn(GetHumanStatus $request) => $request->markRefunded()];
        yield [fn(GetHumanStatus $request) => $request->markSuspended()];
        yield [fn(GetHumanStatus $request) => $request->markUnknown()];
    }
}
