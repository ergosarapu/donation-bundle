<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlanTracking;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('recurring_plan')]
class RecurringPlanProjector implements RecurringPlanProjectionRepositoryInterface
{
    use SubscriberUtil;

    public function __construct(
        private EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function findOne(?RecurringPlanId $id = null, ?RecurringPlanStatus $status = null, ?PaymentMethodId $paymentMethodId = null): ?RecurringPlan
    {
        return $this->findOneByCriteria($this->buildCriteria($id, $status, $paymentMethodId));
    }

    private function findOneOrThrow(?RecurringPlanId $id = null, ?RecurringPlanStatus $status = null): RecurringPlan
    {
        $criteria = $this->buildCriteria($id, $status);
        $recurringPlan = $this->findOneByCriteria($criteria);
        if ($recurringPlan === null) {
            throw new \RuntimeException(sprintf('%s not found for criteria (%s)', RecurringPlan::class, json_encode($criteria)));
        }
        return $recurringPlan;
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?RecurringPlan
    {
        return $this->projectionEntityManager->getRepository(RecurringPlan::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?RecurringPlanId $id = null, ?RecurringPlanStatus $status = null, ?PaymentMethodId $paymentMethodId = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['recurringPlanId'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        if ($paymentMethodId !== null) {
            $criteria['paymentMethodId'] = $paymentMethodId->toString();
        }
        return $criteria;
    }

    #[Subscribe(RecurringPlanInitiated::class)]
    public function onRecurringPlanInitiated(RecurringPlanInitiated $event): void
    {
        if ($this->findOne($event->recurringPlanAction->recurringPlanId) !== null) {
            // Idempotency guard
            return;
        }

        $recurringPlan = new RecurringPlan();
        $recurringPlan->setRecurringPlanId($event->recurringPlanAction->recurringPlanId->toString());
        $recurringPlan->setCreatedAt($event->occuredOn);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setInitialDonationId($event->initialDonationId->toString());
        $recurringPlan->setAmount($event->amount->amount());
        $recurringPlan->setCurrency($event->amount->currency()->code());
        $recurringPlan->setInterval($event->interval->toString());
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setDonorEmail($event->donorIdentity->email?->toString());
        $recurringPlan->setPaymentMethodId($event->recurringPlanAction->paymentMethodId->toString());
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringPlanActivated::class)]
    public function onRecurringPlanActivated(RecurringPlanActivated $event): void
    {
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setNextRenewalTime($event->nextRenewalTime);
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringPlanFailed::class)]
    public function onRecurringPlanFailed(RecurringPlanFailed $event): void
    {
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setNextRenewalTime(null);
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringPlanFailing::class)]
    public function onRecurringPlanFailing(RecurringPlanFailing $event): void
    {
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringPlanRenewalInitiated::class)]
    public function onRecurringPlanRenewalInitiated(RecurringPlanRenewalInitiated $event): void
    {
        $recurringPlan = $this->findOneOrThrow($event->recurringPlanAction->recurringPlanId);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setRenewalInProgressDonationId($event->renewalDonationId->toString());
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringPlanRenewalCompleted::class)]
    public function onRecurringPlanRenewalCompleted(RecurringPlanRenewalCompleted $event): void
    {
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setRenewalInProgressDonationId(null);
        $recurringPlan->setNextRenewalTime($event->nextRenewalTime);
        $this->projectionEntityManager->persist($recurringPlan);
        $this->projectionEntityManager->flush();
    }


    #[Subscribe(DonationAccepted::class)]
    public function onDonationAccepted(DonationAccepted $event): void
    {
        if ($event->recurringPlanId === null) {
            // Not needed for our projection
            return;
        }

        $this->projectionEntityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($event) {
            $tracking = $em->find(RecurringPlanTracking::class, $event->donationId->toString());
            if ($tracking !== null && $tracking->isDonationAcceptedSeen()) {
                // This event has already been processed for this recurring donation
                return;
            }

            if ($tracking === null) {
                $tracking = new RecurringPlanTracking();
                $tracking->setDonationId($event->donationId->toString());
                $em->persist($tracking);
            }
            $tracking->setDonationAcceptedSeen(true);

            $recurringPlan = $this->findOneOrThrow($event->recurringPlanId);
            $cumulativeAmount = $recurringPlan->getCumulativeReceivedAmount() + $event->acceptedAmount->amount();
            $recurringPlan->setCumulativeReceivedAmount($cumulativeAmount);
            $recurringPlan->setUpdatedAt($event->occuredOn);
            $em->flush();
        });
    }



    #[Teardown]
    public function teardown(): void
    {
        $this->projectionEntityManager->wrapInTransaction(function (EntityManagerInterface $em) {
            $em->createQuery('DELETE FROM ' . RecurringPlan::class)->execute();
            $em->createQuery('DELETE FROM ' . RecurringPlanTracking::class)->execute();
        });
    }
}
