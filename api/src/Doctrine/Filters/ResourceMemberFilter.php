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

// Reference https://github.com/api-platform/core/issues/2189.  See how I can also filter based on the request.
// Maybe better to do request filters per: https://api-platform.com/docs/core/filters/#creating-custom-doctrine-orm-filters

// No longer used and use ProjectResourceMemberAclExtension instead, but still need to create other filters.

namespace App\Doctrine\Filters;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\VendorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class ResourceMemberFilter implements FilterInterface
{
    /**
     * @var string
     */
    private const USER = 'user';

    public function __construct(private Security $security)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (($vendor = $this->getUser()) === null) {
            // Currently only filters Vendor. Figure out how to do this right.
            return;
        }

        /*
        $rootAlias = $queryBuilder->getRootAliases()[array_search($resourceClass, $queryBuilder->getRootEntities())];
        //$userTable = lcfirst((new \ReflectionClass($user))->getShortName());
        //sprintf('%s.%s = :user', $alias, $column)
        */
        $queryBuilder
        ->join('o.vendors', 'v')
        ->andWhere('v = :user')
        ->setParameter(self::USER, $vendor);
    }

    // This function is only used to hook in documentation generators (supported by Swagger and Hydra)
    /**
     * @return array<string, array<string, string|array<string, string>|false>>
     */
    public function getDescription(string $resourceClass): array
    {
        return [self::USER => [
            'property' => self::USER,
            'type' => 'string',
            'required' => false,
            'swagger' => [
                'description' => 'Filter using an user',
                'name' => self::USER,
                'type' => 'Vendor',
            ],
        ]];
    }

    private function getUser(): ?VendorInterface
    {
        if (($token = $this->security->getToken()) === null) {
            return null;
        }

        $user = $this->security->getUser();

        if (!$user instanceof VendorInterface) {
            return null;
        }

        return $user;
    }
}
