<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Query\Model\CommandStatus;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Stamp\CorrelationIdStamp;
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
        $this->persistMetadata($message);
        $this->projectionEntityManager->flush();
    }

    private function persistMetadata(Message $message): void
    {
        if (!$message->hasHeader(CorrelationIdStamp::class)) {
            return;
        }
        $correlationId = $message->header(CorrelationIdStamp::class);

        $existing = $this->projectionEntityManager->getRepository(CommandStatus::class)->find($correlationId->toString());
        if ($existing === null) {
            $commandStatus = new CommandStatus(
                correlationId: $correlationId->toString(),
                appliedAt: new DateTimeImmutable()
            );

            $this->persist($commandStatus);
        }

        $this->projectionEntityManager->flush();
    }
}
