<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use ErgoSarapu\DonationBundle\Entity\Campaign;

class CampaignCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string {
        return Campaign::class;
    }
   
    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(6),
            FormField::addFieldset(),
            IdField::new('id')->setDisabled()->setColumns(6),
            DateTimeField::new('createdAt')->setDisabled()->setColumns(6),
            
            FormField::addColumn(6),
            FormField::addFieldset('Details'),
            BooleanField::new('default')->setColumns(6),
            TextField::new('name')->setColumns(6),
            TextField::new('publicTitle')->setColumns(6),
            NumberField::new('publicId')->setColumns(6),
        ];
    }
}
