<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;

/**
 * @extends AbstractCQRSController<Claim>
 */
class ClaimController extends AbstractCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Claim $entity
     */
    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void
    {
        $changes = $updateEvent->getEntityChangeSet();
        /** @var string $field */
        foreach ($changes as $field => $change) {
            /** @var string $oldValue */
            $oldValue = $updateEvent->getOldValue($field);
            /** @var string $newValue */
            $newValue = $updateEvent->getNewValue($field);
            $this->addFlash('warning', sprintf('No command was dispatched for "%s" field change, old value "%s", new value "%s"', $field, $oldValue, $newValue));
        }
    }

    public static function getEntityFqcn(): string
    {
        return Claim::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('claimId')->setDisabled(),
            TextField::new('paymentId')->setDisabled()->setLabel('Payment ID'),
            TextField::new('donationId')->setDisabled()->setLabel('Donation ID'),
            TextField::new('rawName')->setDisabled(),
            TextField::new('givenName')->setDisabled(),
            TextField::new('familyName')->setDisabled(),
            TextField::new('email')->setDisabled(),
            TextField::new('nationalIdCode')->setDisabled()->setLabel('ID Code'),
            TextField::new('iban')->setLabel('IBAN')->setDisabled(),
            TextField::new('reviewReason')->setDisabled()->setLabel('Review reason'),
            IdField::new('identityId')->setDisabled()->hideOnIndex(),
            BooleanField::new('inReview')->setDisabled()->renderAsSwitch(false),
            BooleanField::new('resolved')->setDisabled()->renderAsSwitch(false),
            DateTimeField::new('updatedAt')->setDisabled()->setFormat('yyyy-MM-dd HH:mm')->hideOnIndex(),
            DateTimeField::new('createdAt')->setDisabled()->setFormat('yyyy-MM-dd HH:mm')->hideOnIndex(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'updatedAt' => SortOrder::ASC,
                'createdAt' => SortOrder::ASC,
            ])
            ->setPageTitle(Crud::PAGE_INDEX, 'Identity Claims')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }
}
