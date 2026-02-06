<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

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
