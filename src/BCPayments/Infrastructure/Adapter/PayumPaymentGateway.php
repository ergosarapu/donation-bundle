<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\GatewayCaptureResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayPaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Payum\Core\Payum;

class PayumPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private Payum $payum,
    ) {
    }

    public function capture(GatewayPaymentRequest $gatewayPaymentRequest, PaymentCredentialValue $credentialValue): GatewayCaptureResult
    {
        throw new \Exception('Not implemented');
    }

    public function createCaptureRedirectUrl(GatewayPaymentRequest $gatewayPaymentRequest, bool $requestPaymentMethod): URL
    {
        /** @var Payment $payment */
        $payment = $this->payum->getStorage(Payment::class)->create(); // TODO: Replace with other payment structure
        $payment->setStatus(Status::Created);
        $payment->setNumber($gatewayPaymentRequest->paymentId->toString());
        $payment->setCurrencyCode($gatewayPaymentRequest->amount->currency()->code());
        $payment->setTotalAmount($gatewayPaymentRequest->amount->amount());
        $payment->setDescription($gatewayPaymentRequest->description->toString());
        if ($gatewayPaymentRequest->email !== null) {
            // Is the e-mail really required by Payum?
            $payment->setClientEmail($gatewayPaymentRequest->email->toString());
        }
        $payment->setGateway($gatewayPaymentRequest->gateway->id());

        $this->payum->getStorage(Payment::class)->update($payment);

        $targetUrl = $this->payum->getTokenFactory()->createCaptureToken(
            $gatewayPaymentRequest->gateway->id(),
            $payment,
            'donation_payment_done' // the route to redirect after capture
        )->getTargetUrl();

        return new URL($targetUrl);
    }

}
