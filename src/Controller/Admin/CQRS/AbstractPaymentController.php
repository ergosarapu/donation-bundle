<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;

/**
 * @extends AbstractCQRSController<Payment>
 */
abstract class AbstractPaymentController extends AbstractCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Payment $entity
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
        return Payment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('paymentId')->setDisabled()->hideOnIndex(),
            DateTimeField::new('effectiveDate')->setDisabled()->setLabel('Date')->setFormat('yyyy-MM-dd HH:mm'),
            MoneyField::new('amount')
                ->setCurrencyPropertyPath('currency')
                ->setDisabled()
                ->setCustomOption('currency_format', '%s %s')
                ->formatValue(function ($value, Payment $entity) {
                    return sprintf('%s %s', number_format($entity->getAmount() / 100, 2, '.', ','), $entity->getCurrency());
                })->hideOnForm(),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency')->setDisabled()->onlyOnForms(),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (PaymentStatus $value) {
                    $colors = [
                        PaymentStatus::Initiated->value => 'warning',
                        PaymentStatus::Authorized->value => 'info',
                        PaymentStatus::Captured->value => 'success',
                        PaymentStatus::Settled->value => 'success',
                        PaymentStatus::Failed->value => 'danger',
                        PaymentStatus::Canceled->value => 'secondary',
                        PaymentStatus::Refunded->value => 'secondary',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            TextField::new('effectiveName')->setDisabled()->setLabel('Name'),
            TextField::new('reconciledWith')->setDisabled()->setLabel('Reconciled With')->hideOnIndex(),
            TextField::new('description')->setDisabled(),
            TextField::new('effectiveIdCode')->setDisabled()->setLabel('ID Code'),
            TextField::new('iban')->setDisabled(),
            TextField::new('reference')->setDisabled(),
            DateTimeField::new('updatedAt')->setDisabled()->hideOnIndex(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'effectiveDate' => SortOrder::DESC,
                'paymentId' => SortOrder::DESC,
            ])
        ;
    }

}
