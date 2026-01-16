<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Payum;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Payum\Request\GetPaymentMethodResult;
use ErgoSarapu\DonationBundle\Payum\Request\GetStandingAmount;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHumanStatus;
use RuntimeException;
use Throwable;

class UpdatePaymentStatusExtension implements ExtensionInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function onPreExecute(Context $context)
    {
    }

    public function onExecute(Context $context)
    {
    }

    public function onPostExecute(Context $context)
    {
        $request = $context->getRequest();
        if (!$request instanceof Generic) {
            return;
        }

        $model = $request->getModel();
        if (!$model instanceof ArrayObject) {
            return;
        }

        $payment = $request->getFirstModel();
        if (!$payment instanceof Payment) {
            return;
        }

        $context->getGateway()->execute($status = new GetHumanStatus($model));

        // TODO: handle state machine properly
        $status = $this->getStatus($status->getValue());
        if ($payment->getStatus() !== $status) {
            $payment->setStatus($status);
        }

        $paymentId = PaymentId::fromString($payment->getNumber());

        try {
            $context->getGateway()->execute($standingAmountRequest = new GetStandingAmount($model));
        } catch (RequestNotSupportedException $e) {
            throw new RequestNotSupportedException('No action was found to handle GetStandingAmount request. You should implement and register action for '. GetStandingAmount::class .' request and set the amount because some domain payment state changes require it.', previous: $e);
        }
        $standingAmount = $standingAmountRequest->getAmount();

        try {
            $context->getGateway()->execute($paymentMethodResultRequest = new GetPaymentMethodResult($model));
        } catch (RequestNotSupportedException $e) {
            throw new RequestNotSupportedException('No action was found to handle '. GetPaymentMethodResult::class .' request. You should implement and register action for '. GetPaymentMethodResult::class .' request for the payment method updates to reach domain.', previous: $e);
        }
        $paymentMethodResult = $paymentMethodResultRequest->getResult();

        $event = match ($status) {
            Status::Created, Status::Pending => null,
            Status::Authorized => $standingAmount !== null ? new MarkPaymentAsAuthorized($paymentId, $standingAmount, $paymentMethodResult) : new RuntimeException('Standing amount is required to mark as Authorized'),
            Status::Captured => $standingAmount !== null ? new MarkPaymentAsCaptured($paymentId, $standingAmount, $paymentMethodResult) : new RuntimeException('Standing amount is required to mark as Captured'),
            Status::Failed => new MarkPaymentAsFailed($paymentId, $paymentMethodResult),
            Status::Expired => new MarkPaymentAsFailed($paymentId, $paymentMethodResult),
            Status::Canceled => new MarkPaymentAsCanceled($paymentId),
            Status::Refunded => $standingAmount !== null ? new MarkPaymentAsRefunded($paymentId, $standingAmount) : new RuntimeException('Standing amount is required to mark as Refunded'),
        };

        if ($event instanceof Throwable) {
            throw $event;
        }

        if ($event === null) {
            return;
        }

        // Dispatch status change command
        $this->commandBus->dispatch($event);
    }

    private function getStatus(string $status): Status
    {
        if ($status === 'new') {
            return Status::Created;
        }
        return Status::from($status);
    }
}
