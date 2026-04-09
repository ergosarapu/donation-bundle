<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentMethodProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('payment_method')]
class PaymentMethodProjector implements PaymentMethodProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

    public function findOne(PaymentMethodId $id): ?PaymentMethod
    {
        return $this->findOneByCriteria($this->buildCriteria($id));
    }

    private function findOneOrThrow(PaymentMethodId $id): PaymentMethod
    {
        $criteria = $this->buildCriteria($id);
        $paymentMethod = $this->findOneByCriteria($criteria);
        if ($paymentMethod === null) {
            throw new \RuntimeException(sprintf('%s not found for criteria (%s)', PaymentMethod::class, json_encode($criteria)));
        }
        return $paymentMethod;
    }

    /**
     * @param array<string, string> $criteria
     */
    private function findOneByCriteria(array $criteria): ?PaymentMethod
    {
        return $this->getEntityManager()->getRepository(PaymentMethod::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(PaymentMethodId $id): array
    {
        return ['paymentMethodId' => $id->toString()];
    }

    #[Subscribe(UsablePaymentMethodCreated::class)]
    public function onUsablePaymentMethodCreated(Message $message): void
    {
        $event = $this->getEvent($message, UsablePaymentMethodCreated::class);
        // Idempotency guard
        if ($this->findOne($event->paymentMethodId) !== null) {
            return;
        }
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setPaymentMethodId($event->paymentMethodId->toString());
        $paymentMethod->setUpdatedAt($event->occuredOn);
        $paymentMethod->setCreatedFor($event->createdFor->toString());
        $this->persist($paymentMethod);
        $this->persistTrackingPayload($this->getTrackingId($message), paymentMethodId: $event->paymentMethodId->toString());
        $this->flush($message);
    }

    #[Subscribe(UnusablePaymentMethodCreated::class)]
    public function onUnusablePaymentMethodCreated(Message $message): void
    {
        $event = $this->getEvent($message, UnusablePaymentMethodCreated::class);
        // Idempotency guard
        if ($this->findOne($event->paymentMethodId) !== null) {
            return;
        }
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setPaymentMethodId($event->paymentMethodId->toString());
        $paymentMethod->setUpdatedAt($event->occuredOn);
        $paymentMethod->setCreatedFor($event->createdFor->toString());
        $paymentMethod->setUnusableReason($event->reason);
        $this->persist($paymentMethod);
        $this->persistTrackingPayload($this->getTrackingId($message), paymentMethodId: $event->paymentMethodId->toString());
        $this->flush($message);
    }

    #[Subscribe(PaymentMethodUnusable::class)]
    public function onPaymentMethodUnusable(Message $message): void
    {
        $event = $this->getEvent($message, PaymentMethodUnusable::class);
        $paymentMethod = $this->findOneOrThrow($event->paymentMethodId);
        $paymentMethod->setUpdatedAt($event->occuredOn);
        $paymentMethod->setUnusableReason($event->reason);
        $this->persist($paymentMethod);
        $this->flush($message);
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . PaymentMethod::class)->execute();
    }
}
