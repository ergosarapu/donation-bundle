<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Unit\Payments\Domain;

use DateTimeImmutable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCredentialValue;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodAction;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodId;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodResult;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusableReason;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUsePermitted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUseRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use InvalidArgumentException;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;

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
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodResult = PaymentMethodResult::usable(new PaymentCredentialValue('value'));
        $value = new PaymentCredentialValue('value');
        $createFor = Uuid::uuid7()->toString();

        $this->when(fn () => PaymentMethod::create(
            $this->now,
            $paymentMethodId,
            $paymentMethodResult,
            $createFor,
        ))->then(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $value,
                $createFor,
            ),
        );
    }

    #[DataProvider('unusableReasons')]
    public function testCreateUnusable(PaymentMethodUnusableReason $reason): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodResult = PaymentMethodResult::unusable($reason);
        $createFor = Uuid::uuid7()->toString();

        $this->when(fn () => PaymentMethod::create(
            $this->now,
            $paymentMethodId,
            $paymentMethodResult,
            $createFor,
        ))->then(
            new UnusablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $reason,
                $createFor,
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
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodResult = PaymentMethodResult::usable(new PaymentCredentialValue('new value'));
        $value = new PaymentCredentialValue('value');
        $createFor = Uuid::uuid7()->toString();
        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $value,
                $createFor,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodId, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $paymentMethodId,
                $paymentMethodResult,
            );
        })->then();
    }

    public function testUpdateUsableToUnusable(): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $createFor = Uuid::uuid7()->toString();
        $paymentMethodResult = PaymentMethodResult::unusable(PaymentMethodUnusableReason::Revoked);
        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                new PaymentCredentialValue('value'),
                $createFor,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodId, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $paymentMethodId,
                $paymentMethodResult,
            );
        })->then(
            new PaymentMethodUnusable(
                $this->now,
                $paymentMethodId,
                PaymentMethodUnusableReason::Revoked,
                $createFor,
            ),
        );
    }

    #[DataProvider('unusableReasons')]
    public function testUpdateUnusableIdempotent(PaymentMethodUnusableReason $reason): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodResult = PaymentMethodResult::unusable($reason);
        $createFor = Uuid::uuid7()->toString();
        $this->given(
            new PaymentMethodUnusable(
                $this->now,
                $paymentMethodId,
                $reason,
                $createFor,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodId, $paymentMethodResult) {
            $credential->update(
                $this->now,
                $paymentMethodId,
                $paymentMethodResult,
            );
        })->then();
    }

    public function testUpdateIdMismatchThrows(): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $value = new PaymentCredentialValue('value');
        $createFor = Uuid::uuid7()->toString();

        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $value,
                $createFor
            ),
        )->when(function (PaymentMethod $credential) {
            $otherPaymentMethodId = PaymentMethodId::generate();
            $credential->update(
                $this->now,
                $otherPaymentMethodId,
                PaymentMethodResult::usable(new PaymentCredentialValue('token')),
            );
        })->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Payment Method ID mismatch');
    }

    public function testUsePermitted(): void
    {
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            $paymentMethodId,
            PaymentId::generate(),
        );
        $value = new PaymentCredentialValue('value');
        $createFor = Uuid::uuid7()->toString();

        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $value,
                $createFor,
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
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            $paymentMethodId,
            PaymentId::generate(),
        );
        $createFor = Uuid::uuid7()->toString();
        $this->given(
            new PaymentMethodUnusable(
                $this->now,
                $paymentMethodId,
                $reason,
                $createFor,
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
        $paymentMethodId = PaymentMethodId::generate();
        $paymentMethodActionForUse = PaymentMethodAction::forUse(
            PaymentMethodId::generate(),
            PaymentId::generate(),
        );
        $value = new PaymentCredentialValue('value');
        $createFor = Uuid::uuid7()->toString();
        $this->given(
            new UsablePaymentMethodCreated(
                $this->now,
                $paymentMethodId,
                $value,
                $createFor,
            ),
        )->when(function (PaymentMethod $credential) use ($paymentMethodActionForUse) {
            $credential->use(
                $this->now,
                $paymentMethodActionForUse,
            );
        })->expectsException(InvalidArgumentException::class)->expectsExceptionMessage('Payment Method ID mismatch');
    }
}
