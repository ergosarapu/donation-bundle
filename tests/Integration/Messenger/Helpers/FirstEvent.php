<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('test.first_event')]
class FirstEvent
{
    public function __construct(public readonly string $id)
    {
    }
}
