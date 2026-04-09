<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\TrackingStatus;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\MessageMetadataStamp;
use InvalidArgumentException;
use Patchlevel\EventSourcing\Message\Message;

trait ProjectorTrait
{
    public function __construct(
        private readonly EntityManagerInterface $projectionEntityManager
    ) {
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->projectionEntityManager;
    }

    /**
     * @template T of object
     * @param class-string<T> $eventClass
     * @return T
     */
    private function getEvent(Message $message, string $eventClass): object
    {
        $event = $message->event();
        if (!$event instanceof $eventClass) {
            throw new InvalidArgumentException(sprintf('Event is not instance of %s', $eventClass));
        }
        return $event;
    }

    private function persist(object $entity): void
    {
        $this->projectionEntityManager->persist($entity);
    }

    private function flush(Message $message): void
    {
        $this->persistTrackingPayload($this->getTrackingId($message));
        $this->projectionEntityManager->flush();
    }

    private function getTrackingId(Message $message): string
    {
        if (!$message->hasHeader(MessageMetadataStamp::class)) {
            throw new InvalidArgumentException('MessageMetadataStamp is missing from the message.');
        }
        $metadata = $message->header(MessageMetadataStamp::class);
        return $metadata->trackingId;
    }

    private function persistTrackingPayload(string $trackingId, ?string $paymentId = null, ?string $paymentMethodId = null): void
    {
        $status = $this->projectionEntityManager->getRepository(TrackingStatus::class)->find($trackingId);
        if ($status === null) {
            $status = new TrackingStatus();
            $status->setTrackingId($trackingId);
            $this->persist($status);
        }

        $status->setUpdatedAt(new DateTimeImmutable());
        if ($paymentId !== null) {
            $status->setPaymentId($paymentId);
        }
        if ($paymentMethodId !== null) {
            $status->setPaymentMethodId($paymentMethodId);
        }

        $this->projectionEntityManager->flush();
    }
}
