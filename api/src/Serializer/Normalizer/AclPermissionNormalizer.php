<?php

namespace App\Serializer\Normalizer;

use App\Entity\Acl\AclPermission;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final class AclPermissionNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!$object instanceof AclPermission) {
            throw new InvalidArgumentException(sprintf('The object must be an instance of "%s".', AclPermission::class));
        }
        return ($context['operation']->getClass())::normalize($object);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): AclPermission
    {
        if (!\is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }

        try {
            return ($context['operation']->getClass())::denormalize($data);
        } catch (Exception $e) {
            throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof AclPermission;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === AclPermission::class;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}