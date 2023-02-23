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
// api/src/Serializer/UploadedFileDenormalizer.php

namespace App\Serializer;

use App\Entity\Organization\Tenant;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use ReflectionClass;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Assume a normalizer isn't required since permission isn't provided in collections.
 * Reference: https://api-platform.com/docs/core/serialization/#changing-the-serialization-context-on-a-per-item-basis
 */
final class TenantAccessControlContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(private SerializerContextBuilderInterface $decorated, private Security $security)
    {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;
        // Why did I need to remove:  && false === $normalization
        if ($resourceClass && $resourceClass === Tenant::class && isset($context['groups']) && $this->security->isGranted('ROLE_MANAGE_TENANT_ACL')) {
            $context['groups'] = array_merge($context['groups'], ['acl_admin:read', 'acl_admin:write']);
            array_unique($context['groups']);
        }

        return $context;
    }
}