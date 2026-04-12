<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MovePaymentImportToReview;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentsMatcherInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface;

class TryReconcilePaymentImportHandler implements EventHandlerInterface
{
    private const MINIMUM_SCORE_FOR_AUTO_RECONCILIATION = 0.9;

    public function __construct(
        private readonly PaymentsMatcherInterface $paymentsMatcher,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(PaymentImportPending $event): void
    {
        $matches = $this->paymentsMatcher->match($event->paymentId);

        // Filter matches with score >= 90%
        $highScoreMatches = array_filter(
            $matches,
            fn ($match) => $match->score >= self::MINIMUM_SCORE_FOR_AUTO_RECONCILIATION
        );

        // Only reconcile if exactly one match with high score
        if (count($highScoreMatches) === 1) {
            $match = reset($highScoreMatches);
            $this->commandBus->dispatch(new ReconcilePaymentImport(
                $event->paymentId,
                PaymentId::fromString($match->payment->getPaymentId())
            ));
            return;
        }

        // Otherwise, move to review for manual handling
        $this->commandBus->dispatch(new MovePaymentImportToReview($event->paymentId));
    }
}
