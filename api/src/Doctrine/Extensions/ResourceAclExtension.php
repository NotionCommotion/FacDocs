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

namespace App\Doctrine\Extensions;

use App\Entity\Acl\HasResourceAclInterface;
use App\Security\Service\ResourceAclService;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class ResourceAclExtension implements QueryCollectionExtensionInterface   //, QueryItemExtensionInterface
{
    public function __construct(private ResourceAclService $aclService)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if($this->supports($resourceClass)) {
            $this->aclService->applyDoctrineExtensionConstraint($queryBuilder, $resourceClass);
            //echo($queryBuilder->getDql().PHP_EOL);
        }
    }

    /*
    // Causes item not found error when making a post request along with securityPostDenormalize if the entity has a reference to another resource which the user does not have access to read.
    // Change to use security for getItem requests.
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        if($this->supports($resourceClass)) {
            $this->aclService->applyDoctrineExtensionConstraint($queryBuilder, $resourceClass);
        }
    }
    */

    private function supports(string $resourceClass): bool
    {
        return is_subclass_of($resourceClass, HasResourceAclInterface::class, true);
    }
}
