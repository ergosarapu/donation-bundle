<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\CampaignProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignArchived;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignNameUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitleUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('campaign')]
class CampaignProjector implements CampaignProjectionRepositoryInterface
{
    use SubscriberUtil;

    public function __construct(
        private EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function findOne(?CampaignId $id = null, ?CampaignStatus $status = null): ?Campaign
    {
        return $this->findOneByCriteria($this->buildCriteria($id, $status));
    }

    public function findBy(?CampaignId $id = null, ?CampaignStatus $status = null): array
    {
        return $this->findByCriteria($this->buildCriteria($id, $status));
    }

    private function findOneOrThrow(CampaignId $campaignId): Campaign
    {
        $campaign = $this->findOne($campaignId);
        if ($campaign === null) {
            throw new \RuntimeException(sprintf('%s not found for id %s', Campaign::class, $campaignId->toString()));
        }
        return $campaign;
    }

    /**
     * @param array<string, string> $criteria
     * @return array<Campaign>
     */
    private function findByCriteria(array $criteria): array
    {
        return $this->projectionEntityManager->getRepository(Campaign::class)->findBy($criteria);
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?Campaign
    {
        return $this->projectionEntityManager->getRepository(Campaign::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?CampaignId $id = null, ?CampaignStatus $status = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['campaignId'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        return $criteria;
    }

    #[Subscribe(CampaignCreated::class)]
    public function onCampaignCreated(CampaignCreated $event): void
    {
        // Idempotency guard
        if ($this->findOne($event->campaignId) !== null) {
            return;
        }

        $campaign = new Campaign();
        $campaign->setCampaignId($event->campaignId->toString());
        $campaign->setName($event->name->toString());
        $campaign->setPublicTitle($event->publicTitle->toString());
        $campaign->setStatus($event->status);
        $campaign->setCreatedAt($event->createdAt);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->projectionEntityManager->persist($campaign);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(CampaignNameUpdated::class)]
    public function onCampaignNameUpdated(CampaignNameUpdated $event): void
    {
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setName($event->name->toString());
        $campaign->setUpdatedAt($event->occuredOn);

        $this->projectionEntityManager->persist($campaign);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(CampaignPublicTitleUpdated::class)]
    public function onCampaignPublicTitleUpdated(CampaignPublicTitleUpdated $event): void
    {
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setPublicTitle($event->publicTitle->toString());
        $campaign->setUpdatedAt($event->occuredOn);

        $this->projectionEntityManager->persist($campaign);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(CampaignActivated::class)]
    public function onCampaignActivated(CampaignActivated $event): void
    {
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setStatus($event->status);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->projectionEntityManager->persist($campaign);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(CampaignArchived::class)]
    public function onCampaignArchived(CampaignArchived $event): void
    {
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setStatus($event->status);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->projectionEntityManager->persist($campaign);
        $this->projectionEntityManager->flush();
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->projectionEntityManager->createQuery('DELETE FROM ' . Campaign::class)->execute();
    }
}
