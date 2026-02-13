<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;

class PaymentCQRSController extends AbstractPaymentCQRSController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('(entity.importStatus IS NULL OR entity.importStatus IN (:importStatuses))')
            ->setParameter('importStatuses', [
            PaymentImportStatus::Accepted->value,
            PaymentImportStatus::Reconciled->value,
            ]);
        return $qb;
    }
}
