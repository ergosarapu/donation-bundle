<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
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
            IdField::new('identityId')->setDisabled()->hideOnIndex(),
            TextField::new('givenName')->setDisabled(),
            TextField::new('familyName')->setDisabled(),
            ArrayField::new('rawNames'),
            ArrayField::new('emails'),
            ArrayField::new('ibans')->setLabel('IBANs'),
            TextField::new('nationalIdCode')->setDisabled(),
            ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setSearchFields([
            'identityId',
            'givenName',
            'familyName',
            'nationalIdCode',
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
