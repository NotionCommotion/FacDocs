<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Acl\HasResourceAclInterface;
use App\Security\Service\ResourceAclService;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
//use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;

/*
Since HasDocumentAclInterface extends HasResourceAclInterface, they will be checked as well. Maybe change to use HasAclInterface which both extend?
Tenant handled differently.
*/

class AccessControlAttributeNormalizer implements NormalizerInterface, NormalizerAwareInterface, DenormalizerInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    private const NORMALIZER_ALREADY_CALLED   = 'ACCESS_CONTROL_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';
    private const DENORMALIZER_ALREADY_CALLED = 'ACCESS_CONTROL_ATTRIBUTE_DENORMALIZER_ALREADY_CALLED';

    public function __construct(private ResourceAclService $aclService)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []):bool
    {
        // Not used for get collection requests.  Not sure if I need to check using GetCollection as it seems surpatic.
        return !isset($context[self::NORMALIZER_ALREADY_CALLED]) && $data instanceof HasResourceAclInterface && !(isset($context['operation']) && $context['operation'] instanceof GetCollection);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        // If $context['operation'] is not set, just being used by ApiRequestService to denormalize some test entities.
        //return !isset($context[self::DENORMALIZER_ALREADY_CALLED]) && is_subclass_of($type, HasResourceAclInterface::class);
        return isset($context['operation']) && !isset($context[self::DENORMALIZER_ALREADY_CALLED]) && is_subclass_of($type, HasResourceAclInterface::class);
    }

    public function normalize($object, $format = null, array $context = []):array|string|int|float|bool|\ArrayObject|null
    {
        if($this->aclService->canManageAcl($object->getResourceAcl(), 'manage_acl')) {
            // How can the doc groups just be applied to DocumentAcl and not ResourceAcl?  See https://stackoverflow.com/questions/74336669/controlling-which-nested-resources-serializer-group-context-is-applied
            // TBD whether I should check $object instanceof HasDocumentAclInterface to add document specific groups.
            $context['groups'][] = 'acl_admin:read';
        }
        array_unique($context['groups']);
        $context[self::NORMALIZER_ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function denormalize($data, $type, $format = null, array $context = []):mixed
    {
        if($this->checkDenormalizationStatus($data, $type, $format, $context)) {
            $context['groups'][] = 'acl_admin:write';
        }
        array_unique($context['groups']);
        $context[self::DENORMALIZER_ALREADY_CALLED] = true;
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    private function checkDenormalizationStatus($data, $type, $format, array $context)
    {
        if($context['operation'] instanceof Post) {
            return $this->aclService->canCreateAclEntity($type);
        }
        return $this->aclService->canManageAcl($context['object_to_populate']->getResourceAcl(), 'manage_acl');
    }
}