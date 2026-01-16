<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

enum PaymentMethodUnusableReason: String
{
    case RequestFailed = 'request_failed';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
