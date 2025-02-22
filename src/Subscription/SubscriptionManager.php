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
            $interval = new DateInterval($subscription->getInterval());
            $nextRenewalTime->add($interval);

            $curTimeNextRenewalTime = clone $currentTime;
            $curTimeNextRenewalTime->add($interval);

            # If renewal is processed more than 1 day late, the next renewal time will be based on current time.
            # This guards against processing renewals more frequently than expected by donor. 
            if ($curTimeNextRenewalTime > $nextRenewalTime && $curTimeNextRenewalTime->diff($nextRenewalTime)->days > 1) {
                $nextRenewalTime = $curTimeNextRenewalTime;
            }

            $subscription->setNextRenewalTime($nextRenewalTime);
            $this->entityManager->persist($subscription);

            $payments[] = $payment;
        }
        
        $this->entityManager->flush();
        // Commit before dispatching, so the message can be also handled by synchronous listeners
        $this->entityManager->commit();

        foreach($payments as $payment) {
            $this->messageBus->dispatch(new CapturePayment($payment->getId()));
        }
        
        return count($payments);
    }

    private function createNextPayment(Subscription $subscription): Payment {
        $payment = new Payment();
        $payment->setSubscription($subscription);
        $payment->setStatus(PaymentStatus::Created);
        $payment->setNumber(uniqid(true));
        $payment->setCurrencyCode($subscription->getCurrencyCode());
        $payment->setTotalAmount($subscription->getAmount());

        $initialPayment = $subscription->getInitialPayment();
        $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $initialPayment->getCampaign()?->getPublicId()));
        $payment->setClientId(null);
        $payment->setClientEmail($initialPayment->getClientEmail());
        $payment->setGivenName($initialPayment->getGivenName());
        $payment->setFamilyName($initialPayment->getFamilyName());
        $payment->setNationalIdCode($initialPayment->getNationalIdCode());
        $payment->setCampaign($initialPayment->getCampaign());
        $payment->setGateway($initialPayment->getGateway());
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
