<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanStatus;

/**
 * @extends AbstractCQRSController<RecurringPlan>
 */
class RecurringPlanController extends AbstractCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param RecurringPlan $entity
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
        return RecurringPlan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('recurringPlanId')->setDisabled()->hideOnIndex(),
            DateTimeField::new('initiatedAt')->setDisabled()->setLabel('Date')->setFormat('yyyy-MM-dd HH:mm'),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (RecurringPlanStatus $value) {
                    $styles = [
                        RecurringPlanStatus::Pending->value => ['color' => 'warning', 'icon' => ''],
                        RecurringPlanStatus::Active->value => ['color' => 'success', 'icon' => ''],
                        RecurringPlanStatus::Failing->value => ['color' => 'danger', 'icon' => '⚠️ '],
                        RecurringPlanStatus::Failed->value => ['color' => 'dark', 'icon' => ''],
                        RecurringPlanStatus::Expired->value => ['color' => 'secondary', 'icon' => ''],
                        RecurringPlanStatus::Canceled->value => ['color' => 'secondary', 'icon' => ''],
                    ];
                    $style = $styles[$value->value];
                    return sprintf('<span class="badge badge-%s">%s%s</span>', $style['color'], $style['icon'], ucfirst($value->name));
                }),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency')->setDisabled(),
            MoneyField::new('cumulativeReceivedAmount')->setCurrencyPropertyPath('currency')->setDisabled()->setLabel('Total Received Amount'),
            TextField::new('interval')->setDisabled(),
            EmailField::new('donorEmail')->setDisabled(),
            DateTimeField::new('nextRenewalTime')->setDisabled()->setFormat('yyyy-MM-dd HH:mm'),
            // DateTimeField::new('createdAt')->setDisabled(),
            DateTimeField::new('updatedAt')->setDisabled()->setFormat('yyyy-MM-dd HH:mm'),
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
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
        ;
    }
}
