<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportAccepted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportInReview;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportReconciled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('payment')]
class PaymentProjector implements PaymentProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

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
        return $this->getEntityManager()->getRepository(Payment::class)->findOneBy($criteria);
    }

    /**
     * @return array<string, string>
     */
    private function buildCriteria(?PaymentId $id = null, ?PaymentStatus $status = null): array
    {
        $criteria = [];
        if ($id !== null) {
            $criteria['paymentId'] = $id->toString();
        }
        if ($status !== null) {
            $criteria['status'] = $status->value;
        }
        return $criteria;
    }

    #[Subscribe(PaymentInitiated::class)]
    public function onPaymentInitiated(Message $message): void
    {
        $event = $this->getEvent($message, PaymentInitiated::class);
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setPaymentId($event->paymentId->toString());
        $payment->setInitiatedAt($event->occuredOn);
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $payment->setGateway($event->gateway->id());
        $this->persist($payment);
        $this->flush($message);
    }

    #[Subscribe(PaymentCreated::class)]
    public function onPaymentCreated(Message $message): void
    {
        $event = $this->getEvent($message, PaymentCreated::class);
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setPaymentId($event->paymentId->toString());
        $payment->setInitiatedAt($event->initiatedAt);
        $payment->setCapturedAt($event->capturedAt);
        $payment->setCreatedAt($event->occuredOn);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAmount($event->amount->amount());
        $payment->setCurrency($event->amount->currency()->code());
        $payment->setStatus($event->status);
        $payment->setGateway($event->gateway?->id());
        $payment->setDescription($event->description?->toString());
        $payment->setGivenName($event->name?->givenName);
        $payment->setFamilyName($event->name?->familyName);
        $payment->setNationalIdCode($event->nationalIdCode?->value);
        $payment->setGatewayTransactionId($event->gatewayTransactionId?->value);
        $payment->setBankReference($event->bankReference?->value);
        $payment->setReference($event->paymentReference?->value);
        $payment->setLegacyPaymentNumber($event->legacyPaymentNumber?->value);
        $payment->setIban($event->iban?->value);
        $this->persist($payment);
        $this->flush($message);
    }

    #[Subscribe(PaymentImportPending::class)]
    public function onPaymentImportPending(Message $message): void
    {
        $event = $this->getEvent($message, PaymentImportPending::class);
        // Idempotency guard
        if ($this->findOne($event->paymentId) !== null) {
            return;
        }
        $payment = new Payment();
        $payment->setPaymentId($event->paymentId->toString());
        $payment->setBookingDate($event->bookingDate);
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
        $payment->setReference($event->reference?->value);
        $payment->setIban($event->iban?->value);
        $payment->setBic($event->bic?->value);
        $payment->setSourceIdentifier($event->sourceIdentifier->value);
        $payment->setBankReference($event->bankReference?->value);
        $this->persist($payment);
        $this->flush($message);
    }

    #[Subscribe(PaymentRedirectUrlSetUp::class)]
    public function onPaymentRedirectURLSetUp(Message $message): void
    {
        $event = $this->getEvent($message, PaymentRedirectUrlSetUp::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setRedirectUrl($event->redirectUrl->value());
        $this->flush($message);
    }

    #[Subscribe(PaymentAuthorized::class)]
    public function onPaymentAuthorized(Message $message): void
    {
        $event = $this->getEvent($message, PaymentAuthorized::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setAuthorizedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->authorizedAmount->amount());
        $this->flush($message);
    }

    #[Subscribe(PaymentCaptured::class)]
    public function onPaymentCaptured(Message $message): void
    {
        $event = $this->getEvent($message, PaymentCaptured::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setCapturedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->capturedAmount->amount());
        $this->flush($message);
    }

    #[Subscribe(PaymentCanceled::class)]
    public function onPaymentCanceled(Message $message): void
    {
        $event = $this->getEvent($message, PaymentCanceled::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $this->flush($message);
    }

    #[Subscribe(PaymentFailed::class)]
    public function onPaymentFailed(Message $message): void
    {
        $event = $this->getEvent($message, PaymentFailed::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $this->flush($message);
    }

    #[Subscribe(PaymentRefunded::class)]
    public function onPaymentRefunded(Message $message): void
    {
        $event = $this->getEvent($message, PaymentRefunded::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setStatus($event->status);
        $payment->setAmount($event->remainingAmount->amount());
        $this->flush($message);
    }

    #[Subscribe(PaymentImportAccepted::class)]
    public function onPaymentImportAccepted(Message $message): void
    {
        $event = $this->getEvent($message, PaymentImportAccepted::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setImportStatus($event->importStatus);
        $this->flush($message);
    }

    #[Subscribe(PaymentImportRejected::class)]
    public function onPaymentImportRejected(Message $message): void
    {
        $event = $this->getEvent($message, PaymentImportRejected::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setImportStatus($event->importStatus);
        $this->flush($message);
    }

    #[Subscribe(PaymentImportReconciled::class)]
    public function onPaymentImportReconciled(Message $message): void
    {
        $event = $this->getEvent($message, PaymentImportReconciled::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setImportStatus($event->importStatus);
        $payment->setReconciledWith($event->reconciledWith->toString());
        $this->flush($message);
    }

    #[Subscribe(PaymentImportInReview::class)]
    public function onPaymentImportInReview(Message $message): void
    {
        $event = $this->getEvent($message, PaymentImportInReview::class);
        $payment = $this->findOneOrThrow($event->paymentId);
        $payment->setUpdatedAt($event->occuredOn);
        $payment->setImportStatus($event->importStatus);
        $this->flush($message);
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . Payment::class)->execute();
    }
}
