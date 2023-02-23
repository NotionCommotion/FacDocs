<?php

declare(strict_types=1);

namespace App\Security\Service;

use App\Entity\Acl\ManagedByAclInterface;
use App\Entity\Acl\AclInterface;
use App\Entity\Acl\ResourceAclMember;
use App\Entity\Acl\AclMemberInterface;
use App\Entity\User\BasicUserInterface;
use App\Repository\Acl\AclRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;

/**
 * Voters are not used for GET operations, and all security is handled here.
 * Allows users which do not have the required symfony role to read entities based on the following conditions:
 * 1. For HasResourceAclInterface, if they are a ResourceMemberRoleInterface.
 * 1. For HasDocumentAclInterface:
 * 1.a If entities has public ACL permission.
 * 1.b If entities has owner ACL permission and user is the owner.
 * 1.c If entities has restricted ACL permission, and the user is a ACL member and the member relationship has public access
 * 1.d If entities has restricted ACL permission, and the user is a ACL member and the member relationship has owner access, and the user is the owner.
 * AccessRoleService will provide default role.
 */
abstract class AbstractAclService implements AclServiceInterface
{
    public function __construct(private AclRepositoryInterface $aclRepository, private AccessRoleService $accessRoleService, private Security $security, private EntityManagerInterface $entityManager)
    {
    }

    public function applyDoctrineExtensionConstraint(QueryBuilder $qb, string $resourceClass): bool
    {
        $requiredRole = $this->accessRoleService->getDefaultRole($resourceClass, 'read');
        if ($this->security->isGranted($requiredRole)) {
            // applyDoctrineExtensionConstraint() only confirms required role in member.
            return true;
        }
        return $this->aclRepository->applyDoctrineExtensionConstraint($qb, $this->getUser(), $requiredRole);
    }

    protected function getMember(string $class, AclInterface $acl, BasicUserInterface $user): ?AclMemberInterface
    {
        return $this->entityManager->find($class, ['acl'=>$acl->getId(), 'user'=>$user->getId()]);
    }

    protected function getUser():?BasicUserInterface
    {
        return $this->security->getUser();
    }

    public function debugUser():?array
    {
        return ($user = $this->security->getUser())?$user->debug():null;
    }

    protected function getDefaultRole(string $action, string $subjectClass):string
    {
        if(!$requiredRole = $this->accessRoleService->getDefaultRole($subjectClass, $action)) {
            throw new Exception(sprintf('%s does not support "%s" action.', $subjectClass, $action));
        }
        return $requiredRole;
    }

    protected function isGranted(string $requiredRole):bool
    {
        return $this->security->isGranted($requiredRole);
    }

    //All role specific methods such as AccessRoleService which will provide default role and only located in ResourceAclService.
    protected function isMemberGranted(ResourceAclMember $member, string $requiredRole): bool
    {
        return $this->accessRoleService->isMemberGranted($member, $requiredRole);
    }

    protected function getSecurity(): Security
    {
        return $this->security;
    }
    protected function getAclRepository(): AclRepositoryInterface
    {
        return $this->aclRepository;
    }
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function _echo(string $msg): void
    {
        syslog(LOG_INFO, $msg);
        echo($msg.PHP_EOL);
    }

    protected function debugCaller(int $back=2):string
    {
        $db = debug_backtrace()[$back];
        return sprintf('%s::%s (%s)', $db['class'], $db['function'], $db['line']);
    }
}
