<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler;

use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UsePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use Psr\Clock\ClockInterface;

class UsePaymentMethodHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly ClockInterface $clock,
        private readonly CommandBusInterface $commandBus,
    ) {
    }
    public function __invoke(UsePaymentMethod $command): void
    {
        if (!$this->paymentMethodRepository->has($command->paymentMethodAction->paymentMethodId)) {
            $this->commandBus->dispatch(new MarkPaymentAsFailed($command->paymentMethodAction->paymentId, null));
            return;
        }

        $paymentMethod = $this->paymentMethodRepository->load($command->paymentMethodAction->paymentMethodId);
        $paymentMethod->use($this->clock->now(), $command->paymentMethodAction);
        $this->paymentMethodRepository->save($paymentMethod);
    }
}
