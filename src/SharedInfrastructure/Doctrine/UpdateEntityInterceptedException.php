<?php

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;

/**
 * @template TEntity of object
 */
class UpdateEntityInterceptedException extends Exception
{
    /**
     * @param TEntity $entity 
     */
    public function __construct(private readonly object $entity, private readonly PreUpdateEventArgs $updateEvent)
    {
    }

    /**
     * @return TEntity 
     */
    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getUpdateEvent(): PreUpdateEventArgs
    {
        return $this->updateEvent;
    }
}
