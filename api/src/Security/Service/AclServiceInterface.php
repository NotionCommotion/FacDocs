<?php

declare(strict_types=1);

namespace App\Security\Service;

use App\Entity\Acl\ManagedByAclInterface;
use Doctrine\ORM\QueryBuilder;

interface AclServiceInterface
{
    public function canPerformCrud(ManagedByAclInterface $subject, string $action): bool;
    public function applyDoctrineExtensionConstraint(QueryBuilder $qb, string $resourceClass): bool;
}
