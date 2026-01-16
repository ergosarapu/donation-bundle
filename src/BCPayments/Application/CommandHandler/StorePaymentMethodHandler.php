<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\StorePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\SharedApplication\Exception\AggregateAlreadyExistsException;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class StorePaymentMethodHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(StorePaymentMethod $command): void
    {
        if ($this->paymentMethodRepository->has($command->paymentMethodAction->paymentMethodId)) {
            return;
        }

        $paymentMethod = PaymentMethod::create($this->clock->now(), $command->paymentMethodAction, $command->paymentMethodResult);
        try {
            $this->paymentMethodRepository->save($paymentMethod);
        } catch (AggregateAlreadyExistsException $e) {
            return;
        }
    }

}
