<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan;

/**
 * @extends AbstractCQRSController<RecurringPlan>
 */
class RecurringPlanCQRSController extends AbstractCQRSController
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
            IdField::new('recurringPlanId')->setDisabled()->formatValue(function (string $value): string {
                return substr($value, -12);
            })->hideOnDetail()->hideOnForm(),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency'),
            MoneyField::new('cumulativeReceivedAmount')->setCurrencyPropertyPath('currency')->setDisabled()->setLabel('Total Received Amount'),
            TextField::new('interval'),
            ChoiceField::new('status'),
            EmailField::new('donorEmail'),
            DateTimeField::new('nextRenewalTime')->setDisabled(),
            DateTimeField::new('createdAt')->setDisabled(),
            DateTimeField::new('updatedAt')->setDisabled(),
        ];
    }
}
