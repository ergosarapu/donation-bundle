<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayPaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;

interface PaymentGatewayInterface
{
    public function createCaptureRedirectUrl(GatewayPaymentRequest $gatewayPaymentRequest, bool $requestPaymentMethod): ?URL;

    public function capture(GatewayPaymentRequest $gatewayPaymentRequest, PaymentCredentialValue $credentialValue): GatewayCaptureResult;
}
