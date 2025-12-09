<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\Exception;

use ErgoSarapu\DonationBundle\SharedKernel\Exception\DomainExceptionInterface;
use RuntimeException;

class RecurringPlanRenewalNotAllowedException extends RuntimeException implements DomainExceptionInterface
{
}
