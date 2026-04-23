<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Acceptance\Payments;

use ErgoSarapu\DonationBundle\BCPayments\Application\Port\GatewayCaptureResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\GatewayPaymentRequest;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\URL;
use Exception;
use InvalidArgumentException;

class FakeGateway implements PaymentGatewayInterface
{
    /**
     * @var null|callable(GatewayPaymentRequest, bool): ?URL
     */
    private $createCaptureRedirectUrlBehavior = null;

    /**
     * @var null|callable(GatewayPaymentRequest, PaymentCredentialValue): GatewayCaptureResult
     */
    private $captureBehavior = null;

    public function createCaptureRedirectUrl(GatewayPaymentRequest $gatewayPaymentRequest, bool $requestPaymentMethod): ?URL
    {
        if ($this->createCaptureRedirectUrlBehavior === null) {
            throw new InvalidArgumentException('No behavior defined');
        }
        return ($this->createCaptureRedirectUrlBehavior)($gatewayPaymentRequest, $requestPaymentMethod);
    }

    public function capture(GatewayPaymentRequest $gatewayPaymentRequest, PaymentCredentialValue $credentialValue): GatewayCaptureResult
    {
        if ($this->captureBehavior === null) {
            throw new InvalidArgumentException('No capture behavior defined');
        }
        return ($this->captureBehavior)($gatewayPaymentRequest, $credentialValue);
    }

    /**
     * @param callable(GatewayPaymentRequest, bool): ?URL $behavior
     */
    private function setCreateCaptureRedirectUrlBehaviorCallable(callable $behavior): void
    {
        $this->createCaptureRedirectUrlBehavior = $behavior;
    }

    public function useCreateCaptureRedirectUrlBehavior(?URL $url): void
    {
        $this->setCreateCaptureRedirectUrlBehaviorCallable(
            fn ($_, $requestPaymentMethod) => $url
        );
    }

    /**
     * @param callable(GatewayPaymentRequest, PaymentCredentialValue): GatewayCaptureResult $behavior
     */
    private function setCaptureBehaviorCallable(callable $behavior): void
    {
        $this->captureBehavior = $behavior;
    }

    public function useCaptureBehavior(bool $success, ?Money $capturedAmount = null, bool $transientFailure = false, ?PaymentMethodResult $paymentMethodResult = null): void
    {
        $this->setCaptureBehaviorCallable(
            fn (
                GatewayPaymentRequest $gatewayPaymentRequest,
                PaymentCredentialValue $credentialValue
            ) => new class ($success, $capturedAmount, $transientFailure, $paymentMethodResult) implements GatewayCaptureResult {
                public function __construct(
                    private bool $success,
                    private ?Money $capturedAmount,
                    private bool $transientFailure,
                    private ?PaymentMethodResult $paymentMethodResult,
                ) {
                }

                public function isSuccess(): bool
                {
                    return $this->success;
                }

                public function getCapturedAmount(): Money
                {
                    if ($this->capturedAmount === null) {
                        throw new Exception('Captured amount is not set');
                    }
                    return $this->capturedAmount;
                }

                public function getPaymentMethodResult(): ?PaymentMethodResult
                {
                    return $this->paymentMethodResult;
                }

                public function isTransientFailure(): bool
                {
                    return $this->transientFailure;
                }

                public function getIban(): ?Iban
                {
                    return null;
                }
            }
        );
    }
}
