<?php

declare(strict_types=1);

namespace Superset\Serializer;

use Superset\Config\SerializerConfig;
use Superset\Exception\SerializationException;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SymfonySerializerException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Service for mapping between arrays and DTOs (Data Transfer Objects).
 *
 * Core functionality:
 * - Hydrate: Transform array data into typed DTO objects
 * - Dehydrate: Convert DTO objects back into arrays
 */
final readonly class SerializerService
{
    public function __construct(private readonly Serializer $serializer)
    {
    }

    public static function create(SerializerConfig $config): self
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        $reflectionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();
        $propertyTypeExtractor = new PropertyInfoExtractor(
            listExtractors: [$reflectionExtractor],
            typeExtractors: [$phpDocExtractor, $reflectionExtractor],
            descriptionExtractors: [$phpDocExtractor],
            accessExtractors: [$reflectionExtractor],
            initializableExtractors: [$reflectionExtractor]
        );

        $normalizers = [
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $config->dateTimeFormat,
                DateTimeNormalizer::TIMEZONE_KEY => $config->timeZone,
            ]),
            new BackedEnumNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                classMetadataFactory: $classMetadataFactory,
                nameConverter: $nameConverter,
                propertyTypeExtractor: $propertyTypeExtractor,
                defaultContext: [
                    ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                ]
            ),
        ];

        return new self(new Serializer($normalizers));
    }

    /**
     * Map array data to a DTO object.
     *
     * @template T of object
     *
     * @param array<string, mixed> $data The array data to map
     * @param class-string<T>      $type The target DTO class
     *
     * @return T The DTO instance with the mapped data
     */
    public function hydrate(array $data, string $type): object
    {
        try {
            /** @var T */
            return $this->serializer->denormalize($data, $type);
        } catch (SymfonySerializerException $e) {
            throw new SerializationException('Failed to hydrate data into ' . $type . ': ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Serialize an DTO into an array.
     *
     * @param object $object The DTO to serialize
     *
     * @return array<string, mixed>
     */
    public function dehydrate(object $object): array
    {
        try {
            $result = $this->serializer->normalize($object);

            if (!\is_array($result)) {
                throw new SerializationException('Expected array result but got ' . \get_debug_type($result));
            }

            return $result;
        } catch (SymfonySerializerException $e) {
            throw new SerializationException('Failed to dehydrate object of type ' . $object::class . ': ' . $e->getMessage(), previous: $e);
        }
    }
}
