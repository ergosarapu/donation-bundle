<?php

namespace ErgoSarapu\DonationBundle\Query;

use DateTime;

interface PaymentSummaryQueryInterface
{
    /**
     * @return array<PaymentPeriodSummaryEntryDto>
     */
    public function query(DateTime $startDate, DateTime $endDate, string $groupByPeriod = 'month'): array;

}