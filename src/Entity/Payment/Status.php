<?php

namespace ErgoSarapu\DonationBundle\Entity\Payment;

enum Status: string
{
    case Created = 'created';   // Payment is created into database
    case Pending = 'pending';   // Payment is being processed
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Failed = 'failed';
    case Expired = 'expired';
    case Canceled = 'canceled';
    case Paid = 'paid';         // Use for offline/cash payments
    case Refunded = 'refunded';
    case Unknown = 'unknown';
}
