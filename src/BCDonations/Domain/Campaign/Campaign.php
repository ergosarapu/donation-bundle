<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign;

use DateTimeImmutable;
use LogicException;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate(name: 'campaign')]
class Campaign extends BasicAggregateRoot
{
    #[Id]
    private CampaignId $id;
    private CampaignName $name;
    private CampaignPublicTitle $publicTitle;
    private CampaignStatus $status;

    public static function create(
        DateTimeImmutable $currentTime,
        CampaignId $campaignId,
        CampaignName $name,
        CampaignPublicTitle $publicTitle,
    ): self {
        $campaign = new self();
        $campaign->recordThat(new CampaignCreated(
            $currentTime,
            $campaignId,
            $name,
            $publicTitle,
        ));
        return $campaign;
    }

    public function updateName(DateTimeImmutable $currentTime, CampaignName $name): void
    {
        if ($this->name->toString() === $name->toString()) {
            return; // Idempotency - no change
        }

        $this->recordThat(new CampaignNameUpdated(
            $currentTime,
            $this->id,
            $name,
        ));
    }

    public function updatePublicTitle(DateTimeImmutable $currentTime, CampaignPublicTitle $publicTitle): void
    {
        if ($this->publicTitle->toString() === $publicTitle->toString()) {
            return; // Idempotency - no change
        }

        $this->recordThat(new CampaignPublicTitleUpdated(
            $currentTime,
            $this->id,
            $publicTitle,
        ));
    }

    public function activate(DateTimeImmutable $currentTime): void
    {
        // Idempotency - already active
        if ($this->status === CampaignStatus::Active) {
            return;
        }

        $this->recordThat(new CampaignActivated(
            $currentTime,
            $this->id,
        ));
    }

    private function failTransitionValidation(CampaignStatus $from, CampaignStatus $to): void
    {
        throw new LogicException('Cannot transition from ' . $from->value . ' to ' . $to->value . '.');
    }

    public function archive(DateTimeImmutable $currentTime): void
    {
        // Idempotency - already archived
        if ($this->status === CampaignStatus::Archived) {
            return;
        }
        $this->validateTransitionToArchived();
        $this->recordThat(new CampaignArchived(
            $currentTime,
            $this->id,
        ));
    }

    public function validateTransitionToArchived(): void
    {
        if ($this->status === CampaignStatus::Active) {
            return;
        }
        $this->failTransitionValidation($this->status, CampaignStatus::Archived);
    }

    #[Apply]
    protected function applyCreated(CampaignCreated $event): void
    {
        $this->id = $event->campaignId;
        $this->name = $event->name;
        $this->publicTitle = $event->publicTitle;
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyNameUpdated(CampaignNameUpdated $event): void
    {
        $this->name = $event->name;
    }

    #[Apply]
    protected function applyPublicTitleUpdated(CampaignPublicTitleUpdated $event): void
    {
        $this->publicTitle = $event->publicTitle;
    }

    #[Apply]
    protected function applyActivated(CampaignActivated $event): void
    {
        $this->status = $event->status;
    }

    #[Apply]
    protected function applyArchived(CampaignArchived $event): void
    {
        $this->status = $event->status;
    }
}
