<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\SharedKernel\ValueObject;

use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[ObjectNormalizer]
final class Gateway
{
    private readonly string $id;

    public function __construct(string $id)
    {
        /** @var string $id */
        $id = mb_trim($id);
        if ($id === '') {
            throw new \InvalidArgumentException('Gateway ID cannot be empty.');
        }
        if (!mb_check_encoding($id, 'ASCII')) {
            throw new \InvalidArgumentException('Gateway ID must contain ASCII characters only.');
        }
        if (strlen($id) > 32) {
            throw new \InvalidArgumentException(sprintf('Gateway ID cannot exceed 32 characters, got %d.', strlen($id)));
        }
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }
}
