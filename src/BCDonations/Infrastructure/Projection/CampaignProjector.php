<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\CampaignProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignArchived;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignDonationDescriptionUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignNameUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitleUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('campaign')]
class CampaignProjector implements CampaignProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

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
        return $this->getEntityManager()->getRepository(Campaign::class)->findBy($criteria);
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?Campaign
    {
        return $this->getEntityManager()->getRepository(Campaign::class)->findOneBy($criteria);
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
    public function onCampaignCreated(Message $message): void
    {
        $event = $this->getEvent($message, CampaignCreated::class);
        // Idempotency guard
        if ($this->findOne($event->campaignId) !== null) {
            return;
        }

        $campaign = new Campaign();
        $campaign->setCampaignId($event->campaignId->toString());
        $campaign->setName($event->name->toString());
        $campaign->setPublicTitle($event->publicTitle->toString());
        $campaign->setDonationDescription($event->donationDescription->toString());
        $campaign->setStatus($event->status);
        $campaign->setCreatedAt($event->createdAt);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Subscribe(CampaignNameUpdated::class)]
    public function onCampaignNameUpdated(Message $message): void
    {
        $event = $this->getEvent($message, CampaignNameUpdated::class);
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setName($event->name->toString());
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Subscribe(CampaignPublicTitleUpdated::class)]
    public function onCampaignPublicTitleUpdated(Message $message): void
    {
        $event = $this->getEvent($message, CampaignPublicTitleUpdated::class);
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setPublicTitle($event->publicTitle->toString());
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Subscribe(CampaignActivated::class)]
    public function onCampaignActivated(Message $message): void
    {
        $event = $this->getEvent($message, CampaignActivated::class);
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setStatus($event->status);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Subscribe(CampaignArchived::class)]
    public function onCampaignArchived(Message $message): void
    {
        $event = $this->getEvent($message, CampaignArchived::class);
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setStatus($event->status);
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Subscribe(CampaignDonationDescriptionUpdated::class)]
    public function onCampaignDonationDescriptionUpdated(Message $message): void
    {
        $event = $this->getEvent($message, CampaignDonationDescriptionUpdated::class);
        $campaign = $this->findOneOrThrow($event->campaignId);
        $campaign->setDonationDescription($event->donationDescription->toString());
        $campaign->setUpdatedAt($event->occuredOn);

        $this->persist($campaign);
        $this->flush($message);
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . Campaign::class)->execute();
    }
}
