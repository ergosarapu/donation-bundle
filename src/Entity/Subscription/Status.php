<?php

namespace ErgoSarapu\DonationBundle\Entity\Subscription;

enum Status: string
{
    case Created = 'created'; // Subscription is created into database
    case Active = 'active';
    case Failed = 'failed';
    case Expired = 'expired';
    case Canceled = 'canceled';
}
