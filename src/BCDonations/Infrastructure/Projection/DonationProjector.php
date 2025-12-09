<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanId;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('donation')]
class DonationProjector implements DonationProjectionRepositoryInterface
{
    use SubscriberUtil;

    public function __construct(
        private EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function findBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringPlanId $recurringPlanId = null): array
    {
        $criteria = $this->buildCriteria($id, $status, $recurringPlanId);
        return $this->findByCriteria($criteria);
    }

    public function findOneBy(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringPlanId $recurringPlanId = null): ?Donation
    {
        $criteria = $this->buildCriteria($id, $status);
        return $this->findOneByCriteria($criteria);
    }

    private function findOrThrow(DonationId $donationId): Donation
    {
        $donation = $this->findOneBy($donationId);
        if ($donation === null) {
            throw new \RuntimeException(sprintf('%s not found for id %s', Donation::class, $donationId->toString()));
        }
        return $donation;
    }

    /**
     * @param array<string, string> $criteria
     * @return array<Donation>
     */
    private function findByCriteria(array $criteria): array
    {
        return $this->projectionEntityManager->getRepository(Donation::class)->findBy($criteria);
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?Donation
    {
        return $this->projectionEntityManager->getRepository(Donation::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?DonationId $id = null, ?DonationStatus $status = null, ?RecurringPlanId $recurringPlanId = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['donationId'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        if ($recurringPlanId !== null) {
            $criteria['recurringPlanId'] = $recurringPlanId->toString();
        }
        return $criteria;
    }

    #[Subscribe(DonationInitiated::class)]
    public function onDonationInitiated(DonationInitiated $event): void
    {
        if ($this->findOneBy($event->donationId) !== null) {
            // Idempotency guard
            return;
        }

        $donation = new Donation();
        $donation->setDonationId($event->donationId->toString());
        $donation->setCreatedAt($event->occuredOn);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setPaymentId($event->paymentId->toString());
        $donation->setAmount($event->amount->amount());
        $donation->setCurrency($event->amount->currency()->code());
        $donation->setStatus($event->status);
        $donation->setRecurringPlanId($event->recurringPlanId?->toString());
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(DonationAccepted::class)]
    public function onDonationAccepted(DonationAccepted $event): void
    {
        $donation = $this->findOrThrow($event->donationId);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setStatus($event->status);
        $donation->setRecurringPlanId($event->recurringPlanId?->toString());
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(DonationFailed::class)]
    public function onDonationFailed(DonationFailed $event): void
    {
        $donation = $this->findOrThrow($event->donationId);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setStatus($event->status);
        $donation->setRecurringPlanId($event->recurringPlanId?->toString());
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->projectionEntityManager->createQuery('DELETE FROM ' . Donation::class)->execute();
    }

}
