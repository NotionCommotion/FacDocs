<?php

namespace App\Repository\Acl;

use App\Entity\Acl\AbstractDocumentAcl;
use App\Repository\AbstractRepository;
use App\Entity\Acl\DocumentAclInterface;
use App\Entity\Acl\AclPermissionEnum;
use App\Entity\User\VendorUser;
use App\Entity\Document\Media;
use App\Entity\User\BasicUserInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;


class DocumentAclRepository extends AbstractRepository implements AclRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? AbstractDocumentAcl::class);
    }

    /**
     * Not used?  Use Document::userHasAccessToMedia() Instead?
     * Return true if user has read access to any documents for each of the media in the collection.
     * See self::applyConstraints() for how constraints are applied.
     * Currently just works with projects and not assets and document groups.
     */
    public function userHasAccessToMedia(BasicUserInterface $user, string $requiredRole, Media ...$medias):bool
    {
        $mediaIds = array_unique(array_map(function(Media $media){return $media->getId()->toRfc4122();}, $medias));  // array_unique is probably redundent

        $qb = $this->createQueryBuilder('d');
        $qb
        ->select('m.id')
        ->distinct()
        ->innerJoin('d.medias', 'm')
        ->innerJoin('d.project', 'p')
        ->andWhere($qb->expr()->in('m.id', ':mediaIds'))
        ->setParameter('mediaIds', $mediaIds)
        ;
        $this->applyConstraints($qb, $user, 'd', $requiredRole);
        return count($qb->getQuery()->getResult()) === count($mediaIds);
    }

    /**
     * Called by DocumentAclExtension (which will also bypass this call for admin and system users);
     * See self::applyConstraints() for how constraints are applied.
     * Used to filter Documents and returns results if any of the following are true:Note that ResourceMemberDocumentAclExtension will bypass this check if user is an admin or system user.
     * Assume distinct() applied by core doctrine code?
     * I needed to change the priorty of the filter in service.yaml to do before eager loading but doesn't seem to hurt anything.
     */
    public function applyDoctrineExtensionConstraint(QueryBuilder $qb, BasicUserInterface $user, string $requiredRole): bool
    {
        $rootAlias = $qb->getRootAlias();
        $this->applyConstraints($qb->leftJoin(sprintf('%s.project', $rootAlias), 'p'), $user, $rootAlias, $requiredRole);
        return true;
    }

    /**
     * Filter Documents based on the following criteria:
     * 1. Container is public
     * 2. Container is owned by and user is the owner of the document
     * 3. User is a member and member permission is public
     * 4. User is a member and member permission is owner and user is the owner of the document.
     * Currently just works with projects and not assets and document groups.
     */
    private function applyConstraints(QueryBuilder $qb, BasicUserInterface $user, string $documentAlias, string $requiredRole): QueryBuilder
    {
        // SELECT, Joining Documents, Media, Projects, Assets, DocumentGroups, etc added elsewhere, and Where clause if any added elsewhere.

        $readPermisionField = $this->getReadPermissionField($user, 'acl'); // acl.tenantReadPermission or acl.vendorReadPermission
        $documentOwnerField = sprintf('%s.owner', $documentAlias);
        $whereClauses = [
            $qb->expr()->eq($readPermisionField, ':allow_all_permission'),
            $qb->expr()->eq('mem.readPermission', ':allow_all_permission'),
            $qb->expr()->andX(
                $qb->expr()->eq($readPermisionField, ':allow_owner_permission'),
                $qb->expr()->eq($documentOwnerField, ':user')
            ),
            $qb->expr()->andX(
                $qb->expr()->eq('mem.readPermission', ':allow_owner_permission'),
                $qb->expr()->eq($documentOwnerField, ':user')
            ),
            $qb->expr()->andX(
                $qb->expr()->eq($readPermisionField, ':allow_coworker_permission'),
                $qb->expr()->eq('owner.organization', ':organization')
            ),
            $qb->expr()->andX(
                $qb->expr()->eq('mem.readPermission', ':allow_coworker_permission'),
                $qb->expr()->eq('owner.organization', ':organization')
            ),
            $qb->expr()->eq('roles.id', ':role'),
        ];
        if($user->isTenantUser()) {
            $whereClauses[] = $qb->expr()->andX(
                $qb->expr()->eq($readPermisionField, ':allow_tenant_over_vendor_permission'),
                $qb->expr()->isInstanceOf('owner', VendorUser::class)
            );
            $whereClauses[] = $qb->expr()->andX(
                $qb->expr()->eq('mem.readPermission', ':allow_tenant_over_vendor_permission'),
                $qb->expr()->isInstanceOf('owner', VendorUser::class)
            );
            $qb->setParameter('allow_tenant_over_vendor_permission', AclPermissionEnum::getValueFromName('vendor'));
        }

        return $qb
        ->leftJoin('d.owner', 'owner')
        ->leftJoin('p.documentAcl', 'acl')
        ->leftJoin('acl.members', 'mem', Expr\Join::WITH, 'mem.user = :user')

        // Pertains to the resource ACL and not the document ACL
        ->leftJoin('p.resourceAcl', 'rAcl')
        ->leftJoin('rAcl.members', 'rMem', Expr\Join::WITH, 'rMem.user = :user')
        ->leftJoin('rMem.roleConstraints', 'roles')

        ->andWhere($qb->expr()->orX(...$whereClauses))
        ->setParameter('user', $user->getId(), 'ulid')
        ->setParameter('organization', $user->getOrganizationId(), 'ulid')
        ->setParameter('role', $requiredRole)
        ->setParameter('allow_all_permission', AclPermissionEnum::getValueFromName('all'))
        ->setParameter('allow_owner_permission', AclPermissionEnum::getValueFromName('owner'))
        ->setParameter('allow_coworker_permission', AclPermissionEnum::getValueFromName('coworker'));
        /*
        SELECT o
        FROM App\Entity\Document\Document o
        LEFT JOIN o.project p
        LEFT JOIN p.documentAcl acl
        LEFT JOIN acl.members mem WITH mem.user = :user
        LEFT JOIN mem.user user
        WHERE
        acl.tenantReadPermission = :allow_all_permission
        OR mem.readPermission = :allow_all_permission
        OR (acl.tenantReadPermission = :allow_owner_permission AND o.owner = :user)
        OR (mem.readPermission = :allow_owner_permission AND o.owner = :user)
        OR (acl.tenantReadPermission = :allow_coworker_permission AND user.organization = :organization)
        OR (mem.readPermission = :allow_coworker_permission AND user.organization = :organization)
        OR (acl.tenantReadPermission = :allow_tenant_over_vendor_permission AND user INSTANCE OF App\Entity\User\VendorUser)
        OR (mem.readPermission = :allow_tenant_over_vendor_permission AND user INSTANCE OF App\Entity\User\VendorUser)
        */
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
