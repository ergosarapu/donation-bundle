<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UpdatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class UpdatePaymentMethodHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdatePaymentMethod $command): void
    {
        $paymentMethod = $this->paymentMethodRepository->load($command->action->paymentMethodId);
        $paymentMethod->update($this->clock->now(), $command->action, $command->result);
        $this->paymentMethodRepository->save($paymentMethod);
    }
}
