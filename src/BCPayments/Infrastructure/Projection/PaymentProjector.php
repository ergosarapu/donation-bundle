<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
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

    public function findOne(?PaymentId $id = null, ?PaymentStatus $status = null): ?Payment
    {
        return $this->findOneByCriteria($this->buildCriteria($id, $status));
    }

    private function findOneOrThrow(?PaymentId $id = null, ?PaymentStatus $status = null): Payment
    {
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
    private function findOneByCriteria(array $criteria): ?Payment
    {
        return $this->projectionEntityManager->getRepository(Payment::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?PaymentId $id = null, ?PaymentStatus $status = null): array
    {
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
    public function onPaymentInitiated(PaymentInitiated $event): void
    {
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setId($event->paymentId->toString());
        $payment->setEffectiveDate($event->occuredOn);
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $this->projectionEntityManager->persist($payment);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentCreated::class)]
    public function onPaymentCreated(PaymentCreated $event): void
    {
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setId($event->paymentId->toString());
        $payment->setEffectiveDate($event->effectiveDate);
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $payment->setDescription($event->description->toString());
        $payment->setGivenName($event->debtorName?->givenName);
        $payment->setFamilyName($event->debtorName?->familyName);
        $payment->setNationalIdCode($event->debtorNationalIdCode?->value);
        $this->projectionEntityManager->persist($payment);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentImportPending::class)]
    public function onPaymentImportPending(PaymentImportPending $event): void
    {
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setId($event->paymentId->toString());
        $payment->setEffectiveDate($event->effectiveDate);
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $payment->setImportStatus($event->importStatus);
        $payment->setDescription($event->description?->toString());
        $payment->setAccountHolderName($event->accountHolderName?->value);
        $payment->setNationalIdCode($event->nationalIdCode?->value);
        $payment->setOrganizationRegCode($event->organizationRegCode?->value);
        $payment->setReferenceNumber($event->referenceNumber?->value);
        $payment->setIban($event->iban?->value);
        $payment->setBic($event->bic?->value);
        $payment->setSourceIdentifier($event->sourceIdentifier->value);
        $payment->setPaymentProcessorReference($event->paymentProcessorReference->value);
        $this->projectionEntityManager->persist($payment);
        $this->projectionEntityManager->flush();
    }

    #[Subscribe(PaymentRedirectUrlSetUp::class)]
    public function onPaymentRedirectURLSetUp(
        PaymentRedirectUrlSetUp $event
    ): void {
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setRedirectUrl($event->redirectUrl->value());
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
    public function onPaymentCaptured(PaymentCaptured $event): void
    {
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
    public function teardown(): void
    {
        $this->projectionEntityManager->createQuery('DELETE FROM ' . Payment::class)->execute();
    }
}
