<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;

/**
 * @extends AbstractCQRSController<Identity>
 */
class DonorController extends AbstractCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Identity $entity
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
        return Identity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('identityId')->setDisabled()->hideOnIndex(),
            ArrayField::new('personNamesSummary')->setDisabled()->setLabel('Person Names'),
            ArrayField::new('legalIdentifiers')->setDisabled()->setLabel('Legal IDs'),
            ArrayField::new('rawNames')->setDisabled(),
            ArrayField::new('emails')->setDisabled(),
            ArrayField::new('ibans')->setDisabled()->setLabel('IBANs'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort([
            'identityId' => 'ASC',
        ])
            ->setSearchFields([
            'identityId',
            'personNames.givenName',
            'personNames.familyName',
            'legalIdentifiers.legalIdentifier',
            'rawNames.rawName',
            'emails.email',
            'ibans.iban'])
            ->setPageTitle(Crud::PAGE_INDEX, 'Donors')
            ->showEntityActionsInlined();
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
