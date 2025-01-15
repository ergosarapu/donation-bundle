<?php

namespace ErgoSarapu\DonationBundle\Tests\Integration\Subscription;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Entity\Subscription\Status as SubscriptionStatus;
use ErgoSarapu\DonationBundle\Repository\PaymentRepository;
use ErgoSarapu\DonationBundle\Repository\SubscriptionRepository;
use ErgoSarapu\DonationBundle\Subscription\SubscriptionManager;
use ErgoSarapu\DonationBundle\Tests\Integration\IntegrationTestingKernel;
use Gedmo\Timestampable\TimestampableListener;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class SubscriptionManagerTest extends KernelTestCase 
{
    use InteractsWithMessenger;
    
    private ?EntityManagerInterface $entityManager;

    private SubscriptionManager $subscriptionManager;

    private PaymentRepository $paymentRepository;

    private SubscriptionRepository $subscriptionRepository;

    protected static function getKernelClass(): string
    {
        return IntegrationTestingKernel::class;
    }

    protected function setUp(): void {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->entityManager->getEventManager()->addEventSubscriber(new TimestampableListener());
        $this->subscriptionManager = $this->getContainer()->get(SubscriptionManager::class);
        $this->paymentRepository = $this->getContainer()->get(PaymentRepository::class);
        $this->subscriptionRepository = $this->getContainer()->get(SubscriptionRepository::class);
    }

    public function testSubscriptionRenewedAndPaymentCaptureDispatched(): void {

        $payment = $this->createPayment(100, Status::Captured, '2024-02-01', 'EUR', 'my_gateway');
        $this->entityManager->persist($payment);

        $subscription = $this->subscriptionManager->createSubscription($payment, 'P1M', SubscriptionStatus::Active);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $paymentsCreated = $this->subscriptionManager->renewAndDispatchCapturePayments(new DateTime('2024-03-01'));
        $this->assertEquals(1, $paymentsCreated);
        
        // Ensure we do not get cached entities
        $this->entityManager->clear();

        $payments = $this->paymentRepository->findAll();
        $this->assertCount(2, $payments);

        $subscription = $this->subscriptionRepository->find($subscription->getId());
        $this->assertEquals(new DateTime('2024-04-01'), $subscription->getNextRenewalTime());

        $this->assertSame($subscription, $payments[0]->getSubscription());
        $this->assertSame($subscription, $payments[1]->getSubscription());
        
        $this->bus()->dispatched()->assertCount(1);
    }

    #[DataProvider('ignoredSubscriptionStatuses')]
    public function testNonActiveSubscriptionRenewalIgnored(SubscriptionStatus $status): void {
        $payment = $this->createPayment(100, Status::Captured, '2024-02-01', 'EUR');
        $this->entityManager->persist($payment);

        $subscription = $this->subscriptionManager->createSubscription($payment, 'P1M', $status);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $paymentsCreated = $this->subscriptionManager->renewAndDispatchCapturePayments(new DateTime('2024-03-01'));
        $this->assertEquals(0, $paymentsCreated);

        // Ensure we do not get cached entities
        $this->entityManager->clear();

        $payments = $this->paymentRepository->findAll();
        $this->assertCount(1, $payments);

        $subscription = $this->subscriptionRepository->find($subscription->getId());
        $this->assertEquals(new DateTime('2024-03-01'), $subscription->getNextRenewalTime());
        $this->assertSame($subscription, $payments[0]->getSubscription());

        $this->bus()->dispatched()->assertEmpty();   
    }

    public static function ignoredSubscriptionStatuses(): Generator
    {
        $statuses = array_filter(SubscriptionStatus::cases(), fn($status) => $status !== SubscriptionStatus::Active);
        foreach ($statuses as $status) {
            yield $status->name => [$status];
        }
    }
    private function createPayment(int $totalAmount, Status $status, string $createdAt, string $currency = 'EUR', string $gateway = null): Payment {
        $payment = new Payment();
        $payment->setTotalAmount($totalAmount);
        $payment->setStatus($status);
        $payment->setCreatedAt(new DateTime($createdAt));
        $payment->setCurrencyCode($currency);
        $payment->setGateway($gateway);
        return $payment;
    }
}
