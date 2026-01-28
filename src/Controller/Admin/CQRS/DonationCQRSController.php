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
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationStatus;

/**
 * @extends AbstractCQRSController<Donation>
 */
class DonationCQRSController extends AbstractCQRSController
{
    public function __construct()
    {
    }

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
            IdField::new('donationId')->setDisabled()->formatValue(function (string $value): string {
                return substr($value, -12);
            })->hideOnDetail()->hideOnForm(),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency')->setDisabled(),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (DonationStatus $value) {
                    $colors = [
                        DonationStatus::Pending->value => 'warning',
                        DonationStatus::Accepted->value => 'success',
                        DonationStatus::Failed->value => 'danger',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            IdField::new('paymentId')->setDisabled(),
            IdField::new('recurringPlanId')->setDisabled(),
            IdField::new('campaignId')->setDisabled(),
            DateTimeField::new('createdAt')->setDisabled(),
            DateTimeField::new('updatedAt')->setDisabled(),
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
