<?php

namespace ErgoSarapu\DonationBundle\Subscription;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Subscription;
use ErgoSarapu\DonationBundle\Entity\Subscription\Status as SubscriptionStatus;
use ErgoSarapu\DonationBundle\Entity\Payment\Status as PaymentStatus;
use ErgoSarapu\DonationBundle\Message\CapturePayment;
use ErgoSarapu\DonationBundle\Repository\SubscriptionRepository;
use Symfony\Component\Messenger\MessageBusInterface;

class SubscriptionManager
{
    public function __construct(
        private readonly ?SubscriptionRepository $subscriptionRepository,
        private readonly ?EntityManagerInterface $entityManager,
        private readonly ?MessageBusInterface $messageBus,
    ) {
    }
    
    public function renewAndDispatchCapturePayments(DateTime $currentTime): int {
        $this->entityManager->beginTransaction();
        $qb = $this->subscriptionRepository->createQueryBuilder('s');
        $qb
            ->where('s.nextRenewalTime <= :date')
            ->andWhere('s.status = :status')
            ->setParameter('date', $currentTime)
            ->setParameter('status', SubscriptionStatus::Active);
        $subscriptions = $qb->getQuery()->toIterable();

        $payments = [];
        /** @var Subscription $subscription */
        foreach($subscriptions as $subscription) {
            $payment = $this->createNextPayment($subscription);
            $this->entityManager->persist($payment);
            
            $nextRenewalTime = clone $subscription->getNextRenewalTime();
            $nextRenewalTime->add(new DateInterval($subscription->getInterval()));
            $subscription->setNextRenewalTime($nextRenewalTime);
            $this->entityManager->persist($subscription);

            $payments[] = $payment;
        }
        
        $this->entityManager->flush();
        foreach($payments as $payment) {
            $this->messageBus->dispatch(new CapturePayment($payment->getId()));
        }
        $this->entityManager->commit();
        return count($payments);
    }

    private function createNextPayment(Subscription $subscription): Payment {
        $payment = new Payment();
        $payment->setSubscription($subscription);
        $payment->setStatus(PaymentStatus::Created);
        $payment->setNumber(uniqid());
        $payment->setCurrencyCode($subscription->getCurrencyCode());
        $payment->setTotalAmount($subscription->getAmount());
        $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $subscription->getInitialPayment()->getCampaign()?->getPublicId()));
        $payment->setClientId(null);
        $payment->setClientEmail($subscription->getInitialPayment()->getClientEmail());
        $payment->setGivenName($subscription->getInitialPayment()->getGivenName());
        $payment->setFamilyName($subscription->getInitialPayment()->getFamilyName());
        $payment->setNationalIdCode($subscription->getInitialPayment()->getNationalIdCode());
        $payment->setCampaign($subscription->getInitialPayment()->getCampaign());
        return $payment;
    }

    public function createSubscription(Payment $payment, string $interval, SubscriptionStatus $status = SubscriptionStatus::Created): Subscription {
        $subscription = new Subscription();
        $subscription->setStatus($status);
        $subscription->setInitialPayment($payment);
        $subscription->setAmount($payment->getTotalAmount());
        $subscription->setCurrencyCode($payment->getCurrencyCode());
        $subscription->setInterval($interval);

        $nextRenewalTime = clone $payment->getCreatedAt();
        $nextRenewalTime->add(new DateInterval($interval));
        $subscription->setNextRenewalTime($nextRenewalTime);
        
        $payment->setSubscription($subscription);

        return $subscription;
    }
}
