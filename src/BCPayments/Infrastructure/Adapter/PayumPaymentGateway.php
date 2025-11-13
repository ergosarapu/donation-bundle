<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use InvalidArgumentException;
use Payum\Core\Payum;

class PayumPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private Payum $payum,
    ) {
    }

    public function createPaymentRedirectUrl(Gateway $gateway, PaymentId $paymentId, Money $amount, ShortDescription $description, ?Email $email): URL
    {
        if ($email === null) {
            throw new InvalidArgumentException('Email is required by Payum to create payment redirect URL');
        }
        /** @var Payment $payment */
        $payment = $this->payum->getStorage(Payment::class)->create(); // TODO: Replace with other payment structure
        $payment->setStatus(Status::Created);
        $payment->setNumber($paymentId->toString());
        $payment->setCurrencyCode($amount->currency()->code());
        $payment->setTotalAmount($amount->amount());
        $payment->setDescription($description->toString());
        $payment->setClientEmail($email->toString());
        $payment->setGateway($gateway->id());

        $this->payum->getStorage(Payment::class)->update($payment);

        $targetUrl = $this->payum->getTokenFactory()->createCaptureToken(
            $gateway->id(),
            $payment,
            'donation_payment_done' // the route to redirect after capture
        )->getTargetUrl();

        return new URL($targetUrl);
    }

}
