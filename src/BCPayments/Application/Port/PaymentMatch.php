<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\Port;

use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;

final class PaymentMatch
{
    public function __construct(
        public readonly Payment $payment,
        public readonly float $score,
        /** @var array<string, float> */
        public readonly array $ruleScores,
    ) {
    }
}
