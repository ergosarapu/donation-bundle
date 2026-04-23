<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine;

use Exception;

/**
 * @template TEntity of object
 */
class DeleteEntityInterceptedException extends Exception
{
    /**
     * @param TEntity $entity
     */
    public function __construct(private readonly object $entity)
    {
    }

    /**
     * @return TEntity
     */
    public function getEntity(): object
    {
        return $this->entity;
    }
}
