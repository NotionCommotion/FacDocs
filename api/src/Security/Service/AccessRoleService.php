<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Voter attributes:
 * DocumentsAcls will be ACL_DOCUMENT_* where options are CREATE, READ, UPDATE, or DELETE.
 * ResourceAcls will be ACL_RESOURCE_* where options are READ, UPDATE, MANAGE, MANAGE_ACL
 */
declare(strict_types=1);

namespace App\Security\Service;
use App\DependencyInjection\RoleExtractor\RoleExtractorInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use App\Entity\User\UserInterface;
use App\Entity\Acl\ResourceAclMember;

final class AccessRoleService
{
    private const DEFAULT_ROLES = [
        'create'    => 'ROLE_TENANT_ADMIN',    // Not currently used by ResourceAcls
        'read'      => 'ROLE_TENANT_ADMIN',
        'update'    => 'ROLE_TENANT_ADMIN',
        'delete'    => 'ROLE_TENANT_ADMIN',    // Not currently used by ResourceAcls
        'manage'    => 'ROLE_TENANT_ADMIN',
        'manage_acl'=> 'ROLE_TENANT_ADMIN'
    ];

    // Currently, just performed in voter.  Consider maybe using.
    private array $roleExtractors;

    public function __construct(private array $defaultRoles, private RoleHierarchyInterface $roleHierarchy, RoleExtractorInterface ...$roleExtractors)
    {
        $this->roleExtractors = $roleExtractors;
    }

    public function getDefaultRole(string $class, string $action):?string
    {
        $action = strtolower($action);
        return $this->defaultRoles[$class][$action]??self::DEFAULT_ROLES[$action]??null;
    }

    public function isUserGranted(UserInterface $user, string $requiredRole): bool
    {
        //throw new \Exception('Is this method required?');
        // Only used for PhpUnit testing.  Remove?
        //$this->_debug($requiredRole, $user);
        return $this->areRolesGranted($user->getRoles(), $requiredRole);
    }
    public function isMemberGranted(ResourceAclMember $member, string $requiredRole): bool
    {
        //$this->_debug($requiredRole, $member);
        return $this->areRolesGranted($member->getRoles(), $requiredRole);
    }
    public function areRolesGranted(array $roles, string $requiredRole): bool
    {
        return in_array($requiredRole, $this->roleHierarchy->getReachableRoleNames($roles));
    }

    public function getReachableRoleNames(UserInterface|ResourceAclMember $user): array
    {
        return $this->roleHierarchy->getReachableRoleNames($user->getRoles());
    }
    public function getReachableRoleNamesFromArray(array $roles): array
    {
        return $this->roleHierarchy->getReachableRoleNames($roles);
    }

    public function debug(): array
    {
        return $this->defaultRoles;
    }

    private function _debug(string $requiredRole, UserInterface|ResourceAclMember $user)
    {
        printf(PHP_EOL.'*** %s requiredRole: %s isGranted: %s userType: %s userRoles: %s reachableRoles: %s called by %s'.PHP_EOL,
            __METHOD__,
            $requiredRole,
            in_array($requiredRole, $this->roleHierarchy->getReachableRoleNames($user->getRoles()))?'y':'n',
            get_class($user),
            implode(', ', $user->getRoles()),
            implode(', ', $this->roleHierarchy->getReachableRoleNames($user->getRoles())),
            implode(' < ', $this->getCaller(4)),
        );
    }
    
    private function getCaller(int $count, int $start=2):array
    {
        $end = $start+$count;
        $a=[];
        $db = debug_backtrace();
        for ($i = $start; $i < $end; $i++) {
          $a[] = $db[$i]['class'].':'.$db[$i]['function'];
        }
        return $a;
    }
}