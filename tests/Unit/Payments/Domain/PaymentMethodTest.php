<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUsePermitted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUseRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentMethodId;
use InvalidArgumentException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PaymentMethodTest extends AggregateRootTestCase
{
    private DateTimeImmutable $now;

    protected function aggregateClass(): string
    {
        return PaymentMethod::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2024-02-01 00:00:00');

    }

    public function testCreateUsable(): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodResult = PaymentMethodResult::usable(new PaymentCredentialValue('value'));
        $value = new PaymentCredentialValue('value');
        $this->when(fn () => PaymentMethod::create(
            $this->now,
            $paymentMethodAction,
            $paymentMethodResult
        ))->then(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodAction,
                $value,
            ),
        );
    }

    #[DataProvider('unusableReasons')]
    public function testCreateUnusable(PaymentMethodUnusableReason $reason): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodResult = PaymentMethodResult::unusable($reason);
        $this->when(fn () => PaymentMethod::create(
            $this->now,
            $paymentMethodAction,
            $paymentMethodResult,
        ))->then(
            new UnusablePaymentMethodCreated(
                $this->now,
                $paymentMethodAction,
                $reason,
            ),
        );
    }

    /**
     * @return array<array{0: PaymentMethodUnusableReason}>
     */
    public static function unusableReasons(): array
    {
        return array_map(
            fn (PaymentMethodUnusableReason $reason) => [$reason],
            PaymentMethodUnusableReason::cases()
        );
    }

    public function testUpdateToUsableIgnored(): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodResult = PaymentMethodResult::usable(new PaymentCredentialValue('new value'));
        $value = new PaymentCredentialValue('value');
        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodAction,
                $value,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodAction, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $paymentMethodAction,
                $paymentMethodResult,
            );
        })->then();
    }

    public function testUpdateUsableToUnusable(): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodResult = PaymentMethodResult::unusable(PaymentMethodUnusableReason::Revoked);
        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodAction,
                new PaymentCredentialValue('value'),
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodAction, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $paymentMethodAction,
                $paymentMethodResult,
            );
        })->then(
            new PaymentMethodUnusable(
                $this->now,
                $paymentMethodAction,
                PaymentMethodUnusableReason::Revoked,
            ),
        );
    }

    #[DataProvider('unusableReasons')]
    public function testUpdateUnusableIdempotent(PaymentMethodUnusableReason $reason): void
    {
        $credentialId = PaymentMethodId::generate();
        $creadentialAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodResult = PaymentMethodResult::unusable($reason);
        $this->given(
            new PaymentMethodUnusable(
                $this->now,
                $creadentialAction,
                $reason,
            ),
        )->when(function (PaymentMethod $credential) use ($creadentialAction, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $creadentialAction,
                $paymentMethodResult,
            );
        })->then();
    }

    public function testUpdateIdMismatchThrows(): void
    {
        $credentialId = PaymentMethodId::generate();
        $creadentialAction = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $value = new PaymentCredentialValue('value');

        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $creadentialAction,
                $value,
            ),
        )->when(function (PaymentMethod $credential) {
            $otherPaymentMethodAction = PaymentMethodAction::forUse(
                PaymentMethodId::generate(),
                PaymentId::generate(),
            );
            $credential->update(
                $this->now,
                $otherPaymentMethodAction,
                PaymentMethodResult::usable(new PaymentCredentialValue('token')),
            );
        })->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Payment Method ID mismatch');
    }

    public function testUsePermitted(): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodActionForRequest = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            $credentialId,
            PaymentId::generate(),
        );
        $value = new PaymentCredentialValue('value');

        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodActionForRequest,
                $value,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodActionForUse) {
            $credential->use(
                $this->now,
                $paymentMethodActionForUse,
            );
        })->then(
            new PaymentMethodUsePermitted(
                $this->now,
                $paymentMethodActionForUse,
                $value,
            )
        );
    }

    #[DataProvider('unusableReasons')]
    public function testUseRejected(PaymentMethodUnusableReason $reason): void
    {
        $credentialId = PaymentMethodId::generate();
        $paymentMethodActionForRequest = PaymentMethodAction::forRequest(
            $credentialId,
            PaymentId::generate(),
        );
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            $credentialId,
            PaymentId::generate(),
        );

        $this->given(
            new PaymentMethodUnusable(
                $this->now,
                $paymentMethodActionForRequest,
                $reason,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodActionForUse) {
            $credential->use(
                $this->now,
                $paymentMethodActionForUse,
            );
        })->then(
            new PaymentMethodUseRejected(
                $this->now,
                $paymentMethodActionForUse,
            )
        );
    }

    public function testUseWithMismatchedIdThrows(): void
    {
        $paymentMethodActionForRequest = PaymentMethodAction::forRequest(
            PaymentMethodId::generate(),
            PaymentId::generate(),
        );
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            PaymentMethodId::generate(),
            PaymentId::generate(),
        );
        $value = new PaymentCredentialValue('value');

        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodActionForRequest,
                $value,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodActionForUse) {
            $credential->use(
                $this->now,
                $paymentMethodActionForUse,
            );
        })->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Payment Method ID mismatch');
    }
}
