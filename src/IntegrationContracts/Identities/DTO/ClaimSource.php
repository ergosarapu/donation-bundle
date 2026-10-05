<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\IntegrationContracts\Identities\DTO;

class ClaimSource
{
    public readonly ?string $type;

    public function __construct(
        public readonly SourceContext $context,
        public readonly string $id,
        ?string $type = null,
    ) {
        $this->type = $type;
    }
}
