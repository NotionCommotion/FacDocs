<?php

namespace App\Serializer\Normalizer;

use App\Entity\Acl\AclPermissionSet;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

// How should this be accomplished so that it acts as a patch and uses the old values if none provided?
final class AclPermissionSetNormalizer implements DenormalizerInterface, CacheableSupportsMethodInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): AclPermissionSet
    {
        if (!\is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }
        return AclPermissionSet::createFromArray($data['tenantUser']??[], $data['tenantMember']??[], $data['vendorUser']??[], $data['vendorMember']??[]);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
      return $type === AclPermissionSet::class;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}