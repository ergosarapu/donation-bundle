<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;

/**
 * @extends AbstractCQRSController<Donation>
 */
class DonationCQRSController extends AbstractCQRSController
{
    public function __construct()
    {
    }

    public function dispatchCommandsForPersist(object $entity): void {
    }

    public function dispatchCommandsForDelete(object $entity): void {
    }

    /**
     * @param Donation $entity
     */
    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void {
    }

    public static function getEntityFqcn(): string {
        return Donation::class;
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
