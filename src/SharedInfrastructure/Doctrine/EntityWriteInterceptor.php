<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\PaymentToken;
use ErgoSarapu\DonationBundle\Entity\Subscription;

class EntityWriteInterceptor
{
    public const WRITE_ALLOWLIST = [
        Payment::class,
        PaymentToken::class,
        Campaign::class,
        Subscription::class
    ];

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
        foreach (self::WRITE_ALLOWLIST as $allowedClass) {
            if ($entity instanceof $allowedClass) {
                return true;
            }
        }
        return false;
    }
}
