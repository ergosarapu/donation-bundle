<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Event\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\ValueObject\DonationStatus;
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

    public function findOne(?DonationId $id = null, ?DonationStatus $status = null): ?Donation
    {
        return $this->findOneByCriteria($this->buildCriteria($id, $status));
    }

    private function findOneOrThrow(?DonationId $id = null, ?DonationStatus $status = null): Donation
    {
        $criteria = $this->buildCriteria($id, $status);
        $donation = $this->findOneByCriteria($criteria);
        if ($donation === null) {
            throw new \RuntimeException(sprintf('%s not found for criteria (%s)', Donation::class, json_encode($criteria)));
        }
        return $donation;
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
    private function buildCriteria(?DonationId $id = null, ?DonationStatus $status = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['id'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        return $criteria;
    }

    #[Subscribe(DonationInitiated::class)]
    public function onDonationInitiated(DonationInitiated $event): void
    {
        if ($this->findOne($event->donationId) !== null) {
            // Idempotency guard
            return;
        }

        $donation = new Donation();
        $donation->setId($event->donationId->toString());
        $donation->setCreatedAt($event->occuredOn);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setPaymentId($event->paymentId->toString());
        $donation->setAmount($event->amount->amount());
        $donation->setCurrency($event->amount->currency()->code());
        $donation->setStatus($event->status);
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(DonationAccepted::class)]
    public function onDonationAccepted(DonationAccepted $event): void
    {
        $donation = $this->findOneOrThrow($event->donationId);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setStatus($event->status);
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(DonationFailed::class)]
    public function onDonationFailed(DonationFailed $event): void
    {
        $donation = $this->findOneOrThrow($event->donationId);
        $donation->setUpdatedAt($event->occuredOn);
        $donation->setStatus($event->status);
        $this->projectionEntityManager->persist($donation);
        $this->projectionEntityManager->flush();
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->projectionEntityManager->createQuery('DELETE FROM ' . Donation::class)->execute();
    }

}
