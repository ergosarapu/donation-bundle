<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Campaign;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignArchived;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignDonationDescriptionUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignNameUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitleUpdated;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use LogicException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;

class CampaignTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;
    private CampaignId $campaignId;
    private CampaignName $name;
    private CampaignPublicTitle $publicTitle;
    private ShortDescription $donationDescription;

    protected function aggregateClass(): string
    {
        return Campaign::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-01-26 12:00:00');
        $this->campaignId = CampaignId::generate();
        $this->name = new CampaignName('Internal Campaign Name');
        $this->publicTitle = new CampaignPublicTitle('Public Campaign Title');
        $this->donationDescription = new ShortDescription('Donation Description');
    }

    public function testCreate(): void
    {
        $this->when(fn () => Campaign::create(
            $this->now,
            $this->campaignId,
            $this->name,
            $this->publicTitle,
            $this->donationDescription,
        ))->then(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        );
    }

    public function testUpdateName(): void
    {
        $newName = new CampaignName('Updated Name');

        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updateName(
            $this->now,
            $newName,
        ))
        ->then(
            new CampaignNameUpdated(
                $this->now,
                $this->campaignId,
                $newName,
            )
        );
    }

    public function testUpdateNameIsIdempotent(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updateName(
            $this->now,
            $this->name, // Same name
        ))
        ->then(); // No event recorded
    }

    public function testUpdatePublicTitle(): void
    {
        $newPublicTitle = new CampaignPublicTitle('Updated Public Title');

        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updatePublicTitle(
            $this->now,
            $newPublicTitle,
        ))
        ->then(
            new CampaignPublicTitleUpdated(
                $this->now,
                $this->campaignId,
                $newPublicTitle,
            )
        );
    }

    public function testUpdatePublicTitleIsIdempotent(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updatePublicTitle(
            $this->now,
            $this->publicTitle, // Same title
        ))
        ->then(); // No event recorded
    }

    public function testUpdateDonationDescription(): void
    {
        $newDonationDescription = new ShortDescription('Updated Donation Description');

        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updateDonationDescription(
            $this->now,
            $newDonationDescription,
        ))
        ->then(
            new CampaignDonationDescriptionUpdated(
                $this->now,
                $this->campaignId,
                $newDonationDescription,
            )
        );
    }

    public function testUpdateDonationDescriptionIsIdempotent(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->updateDonationDescription(
            $this->now,
            $this->donationDescription, // Same description
        ))
        ->then(); // No event recorded
    }

    public function testActivateFromDraft(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->activate($this->now))
        ->then(
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            )
        );
    }

    public function testActivateFromArchived(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            ),
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            ),
            new CampaignArchived(
                $this->now,
                $this->campaignId,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->activate($this->now))
        ->then(
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            )
        );
    }

    public function testActivateIsIdempotent(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            ),
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->activate($this->now))
        ->then(); // No event recorded
    }

    public function testArchiveFromActive(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            ),
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->archive($this->now))
        ->then(
            new CampaignArchived(
                $this->now,
                $this->campaignId,
            )
        );
    }

    public function testArchiveIsIdempotent(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            ),
            new CampaignActivated(
                $this->now,
                $this->campaignId,
            ),
            new CampaignArchived(
                $this->now,
                $this->campaignId,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->archive($this->now))
        ->then(); // No event recorded
    }

    public function testCannotArchiveFromDraft(): void
    {
        $this->given(
            new CampaignCreated(
                $this->now,
                $this->campaignId,
                $this->name,
                $this->publicTitle,
                $this->donationDescription,
                $this->now,
            )
        )
        ->when(fn (Campaign $campaign) => $campaign->archive($this->now))
        ->expectsException(LogicException::class)
        ->expectsExceptionMessage('Cannot transition from draft to archived');
    }
}
