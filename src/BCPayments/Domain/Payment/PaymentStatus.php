<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

enum PaymentStatus: String
{
    case Pending = 'pending';
    case Canceled = 'canceled';
    case Failed = 'failed';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Settled = 'settled';
    case Refunded = 'refunded';
}
