<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Domain\Payment;

enum PaymentImportStatus: string
{
    case Pending = 'pending'; // Payment data has been parsed and waiting for further actions
    case Review = 'review'; // Payment import requires manual review
    case Reconciled = 'reconciled'; // Payment has been reconciled with existing payment
    case Accepted = 'accepted'; // Payment has been accepted
    case Rejected = 'rejected'; // Payment has been rejected
}
