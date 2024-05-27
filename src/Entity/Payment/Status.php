<?php

namespace ErgoSarapu\DonationBundle\Entity\Payment;

enum Status: string
{
    case Created = 'created';   // Payment is created into database
    case Pending = 'pending';   // Payment is being processed
    case Captured = 'captured'; // Payment is captured
    case Failed = 'failed';     // Payment is failed
}
