<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers;

use ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelRepositoryWrapperTrait;

class FirstCommandHandler implements CommandHandlerInterface
{
    use PatchlevelRepositoryWrapperTrait;

    public function __invoke(FirstCommand $command): void
    {
        $this->saveAggregate(TestAggregate::create());
    }
}
