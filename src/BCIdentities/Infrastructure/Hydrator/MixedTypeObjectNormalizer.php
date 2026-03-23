<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Hydrator;

use Attribute;

use function is_array;

use Patchlevel\Hydrator\Hydrator;
use Patchlevel\Hydrator\Normalizer\HydratorAwareNormalizer;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\MissingHydrator;
use Patchlevel\Hydrator\Normalizer\NormalizerWithContext;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final class MixedTypeObjectNormalizer implements NormalizerWithContext, HydratorAwareNormalizer
{
    /**
     *
     * @var array<class-string, string> $classToDiscriminatorMap
     */
    private array $classToDiscriminatorMap;

    /** @param array<string, class-string> $discriminatorToClassMap */
    public function __construct(
        private array $discriminatorToClassMap = [],
    ) {
        $this->classToDiscriminatorMap = array_flip($discriminatorToClassMap);
        if (count($this->discriminatorToClassMap) !== count($this->classToDiscriminatorMap)) {
            throw new InvalidArgument('Duplicate class name found in discriminator mapping');
        }
    }
    private Hydrator|null $hydrator = null;

    public function setHydrator(Hydrator $hydrator): void
    {
        $this->hydrator = $hydrator;
    }

    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed
    {
        if (!$this->hydrator) {
            throw new MissingHydrator();
        }
        if ($value === null) {
            return null;
        }
        if (!is_object($value)) {
            throw InvalidArgument::withWrongType('object', $value);
        }
        if (!array_key_exists($value::class, $this->classToDiscriminatorMap)) {
            throw InvalidArgument::withWrongType(implode(', ', array_keys($this->classToDiscriminatorMap)), $value);
        }
        $discriminator = $this->classToDiscriminatorMap[$value::class];

        $normalizer = new ObjectNormalizer($value::class);
        $normalizer->setHydrator($this->hydrator);

        $normalized = $normalizer->normalize($value, $context);
        return [
            'type' => $discriminator,
            'value' => $normalized
        ];
    }

    /** @param array<string, mixed> $context */
    public function denormalize(mixed $value, array $context = []): mixed
    {
        if (!$this->hydrator) {
            throw new MissingHydrator();
        }

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw InvalidArgument::withWrongType('array<string, mixed>', $value);
        }

        $discriminator = $value['type'];
        if (!is_string($discriminator)) {
            throw InvalidArgument::withWrongType('string', $value['type']);
        }

        if (!array_key_exists($discriminator, $this->discriminatorToClassMap)) {
            throw InvalidArgument::withWrongType(implode(', ', array_keys($this->discriminatorToClassMap)), $discriminator);
        }
        $className = $this->discriminatorToClassMap[$discriminator];

        $normalizer = new ObjectNormalizer($className);
        $normalizer->setHydrator($this->hydrator);

        return $normalizer->denormalize($value['value'], $context);
    }

}
