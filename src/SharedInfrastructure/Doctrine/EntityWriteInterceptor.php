<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class EntityWriteInterceptor
{
    /**
     * @var array<string>
     */
    private readonly array $allowedClasses;

    public function __construct(string ... $allowedClasses)
    {
        $this->allowedClasses = $allowedClasses;
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        if ($this->isWriteAllowed($event->getObject())) {
            return;
        }
        throw new UpdateEntityInterceptedException($event->getObject(), $event);
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        if ($this->isWriteAllowed($event->getObject())) {
            return;
        }
        throw new DeleteEntityInterceptedException($event->getObject());
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        if ($this->isWriteAllowed($event->getObject())) {
            return;
        }
        throw new PersistEntityInterceptedException($event->getObject());
    }

    private function isWriteAllowed(object $entity): bool
    {
        foreach ($this->allowedClasses as $allowedClass) {
            if ($entity instanceof $allowedClass) {
                return true;
            }
        }
        return false;
    }
}
