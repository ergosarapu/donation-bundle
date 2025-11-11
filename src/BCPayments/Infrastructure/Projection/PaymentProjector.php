<?php

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Event\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\ValueObject\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('payment')]
class PaymentProjector implements PaymentProjectionRepositoryInterface
{
    use SubscriberUtil;

    public function __construct(
        private EntityManagerInterface $projectionEntityManager
    ) {
    }

    public function findOne(?PaymentId $id = null, ?PaymentStatus $status = null): ?Payment {
        return $this->findOneByCriteria($this->buildCriteria($id, $status));
    }

    private function findOneOrThrow(?PaymentId $id = null, ?PaymentStatus $status = null): Payment {
        $criteria = $this->buildCriteria($id, $status);
        $donation = $this->findOneByCriteria($criteria);
        if ($donation === null) {
            throw new \RuntimeException(sprintf('%s not found for criteria (%s)', Payment::class, json_encode($criteria))); 
        }
        return $donation;
    }

    /**
     * @param array<string, string> $criteria 
     */
    private function findOneByCriteria(array $criteria): ?Payment{
        return $this->projectionEntityManager->getRepository(Payment::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string> 
     */
    private function buildCriteria(?PaymentId $id = null, ?PaymentStatus $status = null): array {
        $criteria = [];
        if ($id !== null) {
            $criteria['id'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        return $criteria;
    }
    
    #[Subscribe(PaymentInitiated::class)]
    public function onPaymentInitiated(PaymentInitiated $event): void {
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setId($event->paymentId->toString());
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $payment->setRedirectUrl($event->redirectUrl->value());
        $this->projectionEntityManager->persist($payment);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentPending::class)]
    public function onPaymentPending(
        PaymentPending $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentAuthorized::class)]
    public function onPaymentAuthorized(
        PaymentAuthorized $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->authorizedAmount->amount());
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentCaptured::class)]
    public function onPaymentCaptured(PaymentCaptured $event): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->capturedAmount->amount());
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentCanceled::class)]
    public function onPaymentCanceled(
        PaymentCanceled $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentFailed::class)]
    public function onPaymentFailed(
        PaymentFailed $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentRefunded::class)]
    public function onPaymentRefunded(
        PaymentRefunded $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->remainingAmount->amount());
        $this->projectionEntityManager->flush();
    }

    #[Teardown]
    public function teardown(): void{
        $this->projectionEntityManager->createQuery('DELETE FROM ' . Payment::class)->execute();
    }
}
