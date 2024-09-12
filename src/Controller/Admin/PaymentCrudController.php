<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\Entity\Payment;

class PaymentCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string {
        return Payment::class;
    }
   
    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(6),
            FormField::addFieldset('Details'),
            IdField::new('id')->setDisabled()->setColumns(4),
            DateTimeField::new('createdAt')->setDisabled()->setColumns(4),
            AssociationField::new('campaign')->setColumns(4),
            TextField::new('number')->setDisabled()->setColumns(4),
            EmailField::new('clientEmail')->setDisabled()->setColumns(4),
            TextField::new('givenName')->setDisabled()->setColumns(4),
            TextField::new('familyName')->setDisabled()->setColumns(4),
            ChoiceField::new('status')->setDisabled()->setColumns(4),
            MoneyField::new('totalAmount')->setCurrencyPropertyPath('currencyCode')->setDisabled()->setColumns(4),

            FormField::addColumn(6),
            FormField::addFieldset('Meta'),
            TextareaField::new('detailsString')->setDisabled()->hideOnIndex(),
        ];
    }
}
