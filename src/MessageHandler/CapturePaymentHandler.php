<?php

namespace ErgoSarapu\DonationBundle\MessageHandler;

use Egulias\EmailValidator\Parser\DomainPart;
use ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\InitiatePaymentHandler;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment as DomainPayment;

use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\Message\CapturePayment;
use ErgoSarapu\DonationBundle\Repository\PaymentRepository;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Payum\Core\Payum;
use Payum\Core\Request\Capture;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CapturePaymentHandler
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private ?Payum $payum,
        private readonly PaymentRepositoryInterface $repository,
        ) {
    }

    public function __invoke(CapturePayment $capturePayment): void
    {
        $payment = $this->paymentRepository->find($capturePayment->getPaymentId());
        $gateway = $this->payum->getGateway($payment->getGateway());
        $paymentId = PaymentId::fromString($payment->getNumber());

        // Keep legacy tests working by create domain payment since status notifications
        // expect it to exist 
        $amount = new Money($payment->getTotalAmount(), new Currency($payment->getCurrencyCode()));
        $description = new ShortDescription($payment->getDescription());
        $domainPayment = DomainPayment::initiate($paymentId, $amount, new Gateway($payment->getGateway()), $description, new URL(''));
        $this->repository->save($domainPayment); 

        $gateway->execute(new Capture($payment));
    }
}
