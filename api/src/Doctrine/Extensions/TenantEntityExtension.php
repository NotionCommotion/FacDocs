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

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User\SystemUser;

//How is PUT, PATCH, DELETE enforced?
final class TenantEntityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): bool
    {
        if (!is_subclass_of($resourceClass, BelongsToTenantInterface::class)) {
            return false;
        }
        $user = $this->security->getUser();

        if (!$tenantId = $user->getTenantId()) {
            if(!$user->isSystemUser())  {
                throw new \Exception('Something wrong happended as this should be a system user and not a '.get_class($user));
            }
            //System user who is not impersonalizing a tenant user.
            return false;
        }
        $queryBuilder
        ->andWhere(sprintf('%s.tenant = :tenant', $queryBuilder->getRootAliases()[0]))
        ->setParameter('tenant', $tenantId->toRfc4122(), 'ulid')
        ;

        return true;
    }
}
