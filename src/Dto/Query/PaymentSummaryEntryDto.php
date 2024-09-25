<?php

namespace ErgoSarapu\DonationBundle\Dto\Query;

use DateTime;

class PaymentSummaryEntryDto
{

    public function __construct(public ?int $campaignId, public ?string $campaignName, public DateTime $startDate, public DateTime $endDate, public string $periodKey, public string $amount, public string $currency)
    {
    }

    public function __toString():string {
        return sprintf(
            '%s, %s, %s, %s, %s, %s, %s',
            $this->campaignId === null ? 'null' : $this->campaignId,
            $this->campaignName === null ? 'null' : $this->campaignName,
            $this->startDate->format('Y-m-d'),
            $this->endDate->format('Y-m-d'),
            $this->periodKey,
            $this->amount,
            $this->currency
        );
    }
}
