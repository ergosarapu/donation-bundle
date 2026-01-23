<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface;

trait TestMessageBusTrait
{
    /**
     * @var array<object>
     */
    private array $dispatched = [];

    /**
     * @var array<string>
     */
    private array $interceptions = [];

    public function __construct(private readonly EventBusInterface|CommandBusInterface $bus)
    {
    }

    public function dispatch(object $message): void
    {
        $this->send($message, true);
    }

    public function send(object $message, bool $intercept = false): void
    {
        $this->dispatched[] = $message;
        if ($intercept) {
            foreach ($this->interceptions as $instanceof) {
                if ($message instanceof $instanceof) {
                    return;
                }
            }
        }
        $this->bus->dispatch($message);
    }
    public function dispatchAndIntercept(object $message, string ... $interceptions): void
    {
        $this->intercept(...$interceptions);
        $this->bus->dispatch($message);
        foreach ($interceptions as $intercept) {
            $this->interceptions = array_filter(
                $this->interceptions,
                fn ($item) => $item !== $intercept
            );
        }
    }

    /**
     * @return array<object>
     */
    public function dispatchedMessages(string $instanceof): array
    {
        $results = [];
        foreach ($this->dispatched as $message) {
            if ($message instanceof $instanceof) {
                $results[] = $message;
            }
        }

        return $results;
    }

    public function assertDispatched(string $instanceof, int $count = 1): void
    {
        $actualCount = 0;
        foreach ($this->dispatched as $message) {
            if ($message instanceof $instanceof) {
                $actualCount++;
            }
        }

        if ($actualCount !== $count) {
            throw new \RuntimeException(sprintf(
                'Expected %d messages of type %s to be dispatched, but got %d.',
                $count,
                $instanceof,
                $actualCount,
            ));
        }
    }

    public function assertNotDispatched(string $instanceof): void
    {
        $this->assertDispatched($instanceof, 0);
    }

    public function intercept(string ... $interceptions): void
    {
        foreach ($interceptions as $instanceof) {
            $this->interceptions[] = $instanceof;
        }
    }

    public function reset(): void
    {
        $this->dispatched = [];
        $this->interceptions = [];
    }

    public function resetDispatched(): void
    {
        $this->dispatched = [];
    }
}
