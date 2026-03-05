<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlanTracking;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('recurring_plan')]
class RecurringPlanProjector implements RecurringPlanProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

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
        return $this->getEntityManager()->getRepository(RecurringPlan::class)->findOneBy($criteria);
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

    #[Subscribe(RecurringPlanCreated::class)]
    public function onRecurringPlanCreated(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanCreated::class);
        if ($this->findOne($event->recurringPlanId) !== null) {
            // Idempotency guard
            return;
        }

        $recurringPlan = new RecurringPlan();
        $recurringPlan->setRecurringPlanId($event->recurringPlanId->toString());
        $recurringPlan->setCreatedAt($event->createdAt);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setInitialDonationId($event->initialDonationId->toString());
        $recurringPlan->setAmount($event->amount->amount());
        $recurringPlan->setCurrency($event->amount->currency()->code());
        $recurringPlan->setInterval($event->interval->toString());
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setDonorEmail($event->donorIdentity->email?->toString());
        $recurringPlan->setPaymentMethodId($event->paymentMethodId->toString());
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanInitiated::class)]
    public function onRecurringPlanInitiated(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanInitiated::class);
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
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanActivated::class)]
    public function onRecurringPlanActivated(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanActivated::class);
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setNextRenewalTime($event->nextRenewalTime);
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanFailed::class)]
    public function onRecurringPlanFailed(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanFailed::class);
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $recurringPlan->setNextRenewalTime(null);
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanFailing::class)]
    public function onRecurringPlanFailing(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanFailing::class);
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setStatus($event->status);
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanRenewalInitiated::class)]
    public function onRecurringPlanRenewalInitiated(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanRenewalInitiated::class);
        $recurringPlan = $this->findOneOrThrow($event->recurringPlanAction->recurringPlanId);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setRenewalInProgressDonationId($event->renewalDonationId->toString());
        $this->persist($recurringPlan);
        $this->flush($message);
    }

    #[Subscribe(RecurringPlanRenewalCompleted::class)]
    public function onRecurringPlanRenewalCompleted(Message $message): void
    {
        $event = $this->getEvent($message, RecurringPlanRenewalCompleted::class);
        $recurringPlan = $this->findOneOrThrow($event->id);
        $recurringPlan->setUpdatedAt($event->occuredOn);
        $recurringPlan->setRenewalInProgressDonationId(null);
        $recurringPlan->setNextRenewalTime($event->nextRenewalTime);
        $this->persist($recurringPlan);
        $this->flush($message);
    }


    #[Subscribe(DonationAccepted::class)]
    public function onDonationAccepted(Message $message): void
    {
        $event = $this->getEvent($message, DonationAccepted::class);
        if ($event->recurringPlanId === null) {
            // Not needed for our projection
            return;
        }

        $this->getEntityManager()->wrapInTransaction(function () use ($event, $message) {
            $tracking = $this->getEntityManager()->find(RecurringPlanTracking::class, $event->donationId->toString());
            if ($tracking !== null && $tracking->isDonationAcceptedSeen()) {
                // This event has already been processed for this recurring donation
                return;
            }

            if ($tracking === null) {
                $tracking = new RecurringPlanTracking();
                $tracking->setDonationId($event->donationId->toString());
                $this->persist($tracking);
            }
            $tracking->setDonationAcceptedSeen(true);

            $recurringPlan = $this->findOneOrThrow($event->recurringPlanId);
            $cumulativeAmount = $recurringPlan->getCumulativeReceivedAmount() + $event->acceptedAmount->amount();
            $recurringPlan->setCumulativeReceivedAmount($cumulativeAmount);
            $recurringPlan->setUpdatedAt($event->occuredOn);
            $this->flush($message);
        });
    }



    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->wrapInTransaction(function () {
            $this->getEntityManager()->createQuery('DELETE FROM ' . RecurringPlan::class)->execute();
            $this->getEntityManager()->createQuery('DELETE FROM ' . RecurringPlanTracking::class)->execute();
        });
    }
}
