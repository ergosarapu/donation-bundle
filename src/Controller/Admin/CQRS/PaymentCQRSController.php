<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ChangePaymentAmount;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;

/**
 * @extends AbstractCQRSController<Payment>
 */
class PaymentCQRSController extends AbstractCQRSController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function dispatchCommandsForPersist(object $entity): void {
    }

    public function dispatchCommandsForDelete(object $entity): void {
    }

    /**
     * @param Payment $entity
     */
    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void {
        
        // Example:
        $changes = $updateEvent->getEntityChangeSet();
        foreach ($changes as $field => $change) {
            $command = match ($field) {
                // 'amount' => new ChangePaymentAmount(PaymentId::fromString($entity->getId()), new Money($updateEvent->getNewValue($field), new Currency($entity->getCurrency()))),
                default => null,
            };
            if ($command !== null){
                $this->commandBus->dispatch($command);
            } else {
                $this->addFlash('warning', sprintf('No command was dispatched for "%s" field change.', $field));
            }
        }
    }

    public static function getEntityFqcn(): string {
        return Payment::class;
    }
   
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->setDisabled(),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency'),
            TextField::new('status'),
        ];
    }
}
