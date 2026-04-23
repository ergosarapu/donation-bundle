<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan;

enum RecurringPlanActionIntent: String
{
    case Init = 'init';
    case Renew = 'renew';
}
