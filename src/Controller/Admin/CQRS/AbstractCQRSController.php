<?php

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\PersistEntityInterceptedException;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\DeleteEntityInterceptedException;
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
        try{
            parent::updateEntity($entityManager, $entityInstance);
        } catch(UpdateEntityInterceptedException $e){
            $this->dispatchCommandsForUpdate($e->getEntity(), $e->getUpdateEvent());
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try{
            parent::persistEntity($entityManager, $entityInstance);
        } catch(PersistEntityInterceptedException $e){
            $this->dispatchCommandsForPersist($e->getEntity());
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try{
            parent::deleteEntity($entityManager, $entityInstance);
        } catch(DeleteEntityInterceptedException $e){
            $this->dispatchCommandsForDelete($e->getEntity());
        }
    }

    /**
     * @param TEntity $entity
     */
    public abstract function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void;

    /**
     * @param TEntity $entity
     */
    public abstract function dispatchCommandsForPersist(object $entity): void;

    /**
     * @param TEntity $entity
     */
    public abstract function dispatchCommandsForDelete(object $entity): void;
}
