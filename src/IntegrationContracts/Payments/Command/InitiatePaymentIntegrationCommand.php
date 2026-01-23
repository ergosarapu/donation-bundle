<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command;

use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRequest;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;

class InitiatePaymentIntegrationCommand implements IntegrationCommandInterface
{
    public function __construct(
        public readonly PaymentRequest $paymentRequest,
    ) {
    }
}
