<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\DeleteEntityInterceptedException;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\PersistEntityInterceptedException;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\UpdateEntityInterceptedException;

/**
 * @template TEntity of object
 *
 * @extends  AbstractCrudController<TEntity>
 */
abstract class AbstractCQRSController extends AbstractCrudController
{
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::updateEntity($entityManager, $entityInstance);
        } catch (UpdateEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForUpdate($entity, $e->getUpdateEvent());
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::persistEntity($entityManager, $entityInstance);
        } catch (PersistEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForPersist($entity);
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::deleteEntity($entityManager, $entityInstance);
        } catch (DeleteEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForDelete($entity);
        }
    }

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void;

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForPersist(object $entity): void;

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForDelete(object $entity): void;
}
