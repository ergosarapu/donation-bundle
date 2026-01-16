<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

enum PaymentMethodActionIntent: String
{
    case Use = 'use';
    case Request = 'request';
}
