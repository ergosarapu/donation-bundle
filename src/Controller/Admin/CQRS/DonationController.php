<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;

/**
 * @extends AbstractCQRSController<Donation>
 */
class DonationController extends AbstractCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Donation $entity
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
        return Donation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('donationId')->setDisabled()->hideOnIndex(),
            DateTimeField::new('effectiveDate')->setDisabled()->setLabel('Date')->setFormat('yyyy-MM-dd HH:mm'),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency')->setDisabled(),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (DonationStatus $value) {
                    $colors = [
                        DonationStatus::Initiated->value => 'warning',
                        DonationStatus::Created->value => 'warning',
                        DonationStatus::Accepted->value => 'success',
                        DonationStatus::Failed->value => 'danger',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            TextField::new('givenName')->setDisabled(),
            TextField::new('familyName')->setDisabled(),
            TextField::new('email')->setDisabled(),
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
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN);
    }

}
