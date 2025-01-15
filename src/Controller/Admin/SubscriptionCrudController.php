<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\Entity\Subscription;

class SubscriptionCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string {
        return Subscription::class;
    }
   
    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(6),
            FormField::addFieldset(),
            IdField::new('id')->setDisabled()->setColumns(6),
            DateTimeField::new('createdAt')->setDisabled()->setColumns(6),
            
            FormField::addFieldset('Payments'),
            CollectionField::new('payments')->setDisabled()->hideOnIndex(),

            FormField::addColumn(6),
            FormField::addFieldset('Details'),
            AssociationField::new('initialPayment')->setDisabled()->hideOnIndex()->setColumns(6),
            TextField::new('interval')->setDisabled()->setColumns(6),
            MoneyField::new('amount')->setCurrencyPropertyPath('currencyCode')->setDisabled()->setColumns(6),
            DateTimeField::new('nextRenewalTime')->setDisabled()->setColumns(6),
            ChoiceField::new('status')->setColumns(6),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['createdAt' => 'DESC']);
    }
}
