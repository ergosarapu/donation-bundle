<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use RuntimeException;

#[ObjectNormalizer]
final class ClaimSource
{
    private function __construct(
        public readonly ClaimSourceContext $context,
        public readonly string $id,
        public readonly ?string $type = null,
        public readonly ?ClaimId $claimId = null,
    ) {
    }

    public static function create(
        ClaimSourceContext $context,
        string $id,
        ?string $type = null,
        ?ClaimId $claimId = null,
    ): self
    {
        return new self($context, $id, $type, $claimId);
    }

    public function resolve(ClaimId $claimId): self
    {
        return new self($this->context, $this->id, $this->type, $claimId);
    }

    public function claimId(): ClaimId
    {
        if ($this->claimId === null) {
            throw new RuntimeException(sprintf(
                'Claim source "%s:%s" is not resolved.',
                $this->context->value,
                $this->id,
            ));
        }

        return $this->claimId;
    }
}
