<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ArchiveCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignDonationDescription;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignStatus;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCQRSController<Campaign>
 */
class CampaignController extends AbstractCQRSController
{
    /** @param $entity Campaign */
    public function dispatchCommandsForPersist(object $entity): void
    {
        $command = new CreateCampaign(
            new CampaignName($entity->getName()),
            new CampaignPublicTitle($entity->getPublicTitle()),
            new ShortDescription($entity->getDonationDescription()),
        );
        $this->dispatch($command);
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Campaign $entity
     */
    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void
    {
        $changes = $updateEvent->getEntityChangeSet();

        if (isset($changes['name'])) {
            /** @var string $newName */
            $newName = $updateEvent->getNewValue('name');
            $command = new UpdateCampaignName(
                CampaignId::fromString($entity->getCampaignId()),
                new CampaignName($newName),
            );
            $this->dispatch($command);
            unset($changes['name']);
        }

        if (isset($changes['publicTitle'])) {
            /** @var string $newName */
            $newName = $updateEvent->getNewValue('publicTitle');
            $command = new UpdateCampaignPublicTitle(
                CampaignId::fromString($entity->getCampaignId()),
                new CampaignPublicTitle($newName),
            );
            $this->dispatch($command);
            unset($changes['publicTitle']);
        }

        if (isset($changes['donationDescription'])) {
            /** @var string $newDescription */
            $newDescription = $updateEvent->getNewValue('donationDescription');
            $command = new UpdateCampaignDonationDescription(
                CampaignId::fromString($entity->getCampaignId()),
                new ShortDescription($newDescription),
            );
            $this->dispatch($command);
            unset($changes['donationDescription']);
        }
    }

    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('campaignId')->setDisabled()->hideOnIndex(),
            TextField::new('name'),
            TextField::new('publicTitle'),
            TextField::new('donationDescription'),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (CampaignStatus $value) {
                    $colors = [
                        CampaignStatus::Active->value => 'success',
                        CampaignStatus::Archived->value => 'secondary',
                        CampaignStatus::Draft->value => 'warning',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            DateTimeField::new('createdAt')->setFormat('yyyy-MM-dd HH:mm'),
            DateTimeField::new('updatedAt')->setFormat('yyyy-MM-dd HH:mm'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $activate = $this->newInterceptedAction('activate')
            ->linkToCrudAction('activateCampaign')
            ->displayIf(static fn (Campaign $campaign): bool => !$campaign->isActive())
            ->asSuccessAction();
        $archive = $this->newInterceptedAction('archive')
            ->linkToCrudAction('archiveCampaign')
            ->displayIf(static fn (Campaign $campaign): bool => $campaign->isActive())
            ->asWarningAction();

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_EDIT, $activate)
            ->add(Crud::PAGE_EDIT, $archive);
    }

    /**
     * @param AdminContext<Campaign> $context
     */
    #[AdminAction(methods: ['POST'])]
    #[AdminRoute(path: '/reject-campaign')]
    public function activateCampaign(AdminContext $context): Response
    {
        /** @var Campaign $campaign */
        $campaign = $context->getEntity()->getInstance();
        $command = new ActivateCampaign(
            CampaignId::fromString($campaign->getCampaignId())
        );
        return $this->dispatchAndReturnTrackingId($command);
    }

    /**
     * @param AdminContext<Campaign> $context
     */
    #[AdminAction(methods: ['POST'])]
    #[AdminRoute(path: '/archive-campaign')]
    public function archiveCampaign(AdminContext $context): Response
    {
        /** @var Campaign $campaign */
        $campaign = $context->getEntity()->getInstance();
        $command = new ArchiveCampaign(
            CampaignId::fromString($campaign->getCampaignId())
        );
        return $this->dispatchAndReturnTrackingId($command);
    }
}
