<?php

namespace App\Repository\Acl;

use App\Entity\Acl\AbstractResourceAcl;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\AclPermissionEnum;
use App\Repository\AbstractRepository;
use App\Entity\User\BasicUserInterface;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

class ResourceAclRepository extends AbstractRepository implements AclRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? AbstractResourceAcl::class);
    }

    // Checks member roles but not user roles which must be checked previously.
    public function applyDoctrineExtensionConstraint(QueryBuilder $qb, BasicUserInterface $user, string $requiredRole): bool
    {
        $whereClauses = [
            $qb->expr()->eq($this->getReadPermissionField($user, 'acl'), ':allow_all_permission'),
            $qb->expr()->eq('mem.readPermission', ':allow_all_permission'),
        ];
        // Figure this out.
        if(false && $requiredRole) {
            $whereClauses[] = $qb->expr()->eq('roles.id', ':role');
            $qb
            ->leftJoin('mem.roles', 'roles')
            ->setParameter('role', $requiredRole);
        }
        if($this instanceof HasContainerAclInterface && $user->isTenantUser()) {
            // Future.  Somehow allow user to access if they have appropropriate authority on parent class (i.e. can manage vendors so can manage vendor users)
        }

        $qb
        ->leftJoin($qb->getRootAlias().'.resourceAcl', 'acl')
        ->leftJoin('acl.members', 'mem', Expr\Join::WITH, 'mem.user = :user')
        ->andWhere($qb->expr()->orX(...$whereClauses))
        ->setParameter('user', $user->getId(), 'ulid')
        ->setParameter('allow_all_permission', AclPermissionEnum::getValueFromName('all'));

        return true;
    }

    private function getAlias(string $property, array $allAliases): ?string
    {
        for ($i = 1; $i < \count($allAliases); ++$i) {
            if (str_starts_with($allAliases[$i], $property)) {
                return $allAliases[$i];
            }
        }

        return null;
    }

    private function getReadPermissionField(BasicUserInterface $user, string $alias): string
    {
        return sprintf('%s.%sReadPermission', $alias, $user->isTenantUser()?'tenant':'vendor');
    }
}
