<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringDonationTracking;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringDonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\RecurringDonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringDonation\ValueObject\RecurringDonationStatus;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('recurring_donation')]
class RecurringDonationProjector implements RecurringDonationProjectionRepositoryInterface
{
    use SubscriberUtil;

    public function __construct(
        private EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function findOne(?RecurringDonationId $id = null, ?RecurringDonationStatus $status = null): ?RecurringDonation
    {
        return $this->findOneByCriteria($this->buildCriteria($id, $status));
    }

    private function findOneOrThrow(?RecurringDonationId $id = null, ?RecurringDonationStatus $status = null): RecurringDonation
    {
        $criteria = $this->buildCriteria($id, $status);
        $recurringDonation = $this->findOneByCriteria($criteria);
        if ($recurringDonation === null) {
            throw new \RuntimeException(sprintf('%s not found for criteria (%s)', RecurringDonation::class, json_encode($criteria)));
        }
        return $recurringDonation;
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?RecurringDonation
    {
        return $this->projectionEntityManager->getRepository(RecurringDonation::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?RecurringDonationId $id = null, ?RecurringDonationStatus $status = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['recurringDonationId'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        return $criteria;
    }

    #[Subscribe(RecurringDonationInitiated::class)]
    public function onRecurringDonationInitiated(RecurringDonationInitiated $event): void
    {
        if ($this->findOne($event->id) !== null) {
            // Idempotency guard
            return;
        }

        $recurringDonation = new RecurringDonation();
        $recurringDonation->setRecurringDonationId($event->id->toString());
        $recurringDonation->setCreatedAt($event->occuredOn);
        $recurringDonation->setUpdatedAt($event->occuredOn);
        $recurringDonation->setActivationDonationId($event->activationDonationId->toString());
        $recurringDonation->setAmount($event->amount->amount());
        $recurringDonation->setCurrency($event->amount->currency()->code());
        $recurringDonation->setInterval($event->interval->toString());
        $recurringDonation->setStatus($event->status);
        $recurringDonation->setDonorEmail($event->donorEmail->toString());
        $this->projectionEntityManager->persist($recurringDonation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(RecurringDonationActivated::class)]
    public function onRecurringDonationActivated(RecurringDonationActivated $event): void
    {
        $recurringDonation = $this->findOneOrThrow($event->id);
        $recurringDonation->setUpdatedAt($event->occuredOn);
        $recurringDonation->setStatus($event->status);
        $this->projectionEntityManager->persist($recurringDonation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(DonationAccepted::class)]
    public function onDonationAccepted(DonationAccepted $event): void
    {
        if ($event->recurringDonationId === null) {
            // Not needed for our projection
            return;
        }

        $this->projectionEntityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($event) {
            $tracking = $em->find(RecurringDonationTracking::class, $event->donationId->toString());
            if ($tracking !== null && $tracking->isDonationAcceptedSeen()) {
                // This event has already been processed for this recurring donation
                return;
            }

            if ($tracking === null) {
                $tracking = new RecurringDonationTracking();
                $tracking->setDonationId($event->donationId->toString());
                $em->persist($tracking);
            }
            $tracking->setDonationAcceptedSeen(true);

            $recurringDonation = $this->findOneOrThrow($event->recurringDonationId);
            $cumulativeAmount = $recurringDonation->getCumulativeReceivedAmount() + $event->acceptedAmount->amount();
            $recurringDonation->setCumulativeReceivedAmount($cumulativeAmount);
            $recurringDonation->setUpdatedAt($event->occuredOn);
            $em->flush();
        });
    }



    #[Teardown]
    public function teardown(): void
    {
        $this->projectionEntityManager->wrapInTransaction(function (EntityManagerInterface $em) {
            $em->createQuery('DELETE FROM ' . RecurringDonation::class)->execute();
            $em->createQuery('DELETE FROM ' . RecurringDonationTracking::class)->execute();
        });
    }
}
