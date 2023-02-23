<?php

declare(strict_types=1);

namespace App\Security\Service;

use App\Entity\Acl\ManagedByAclInterface;
use App\Entity\Acl\AclEntityInterface;
use App\Entity\Acl\HasContainerAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\ResourceAclMember;
use App\Entity\User\BasicUserInterface;
use App\Repository\Acl\ResourceAclRepository;

use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

final class ResourceAclService extends AbstractAclService
{
    private const ACL_VALID_CRUD_ACTIONS = ['read', 'update'];  //, 'manage'];

    public function __construct(ResourceAclRepository $aclRepository, AccessRoleService $accessRoleService, Security $security, EntityManagerInterface $entityManager)
    {
        parent::__construct($aclRepository, $accessRoleService, $security, $entityManager);
    }

    // action is read, update
    public function canPerformCrud(ManagedByAclInterface $resourceWithAcl, string $action): bool
    {
        if(!$user = $this->getUser()) {
            return false;
        }

        $action = strtolower($action);

        $requiredRole = $this->getDefaultRole($action, get_class($resourceWithAcl));

        if ($this->_canPerformCrud($resourceWithAcl->getResourceAcl(), $user, $requiredRole, $action)) {
            return true;
        }

        if($resourceWithAcl instanceof HasContainerAclInterface) {
            // Check if parent has access.  Only pertains currently to vendorUsers.
            return $this->_canPerformCrud($resourceWithAcl->getContainer()->getResourceAcl(), $user, $requiredRole, $action);
        }

        return false;
    }
    private function _canPerformCrud(?ResourceAclInterface $acl, BasicUserInterface $user, string $requiredRole, string $action): bool
    {
        if ($this->isGranted($requiredRole)) {
            return true;
        }
        if(!$acl) {
            return false;
        }

        // Must be performed after allowing users with appropriate roles.  TBD whether a member with the appropriate role should be able to delete (but never create since nothing to be a member of).
        if(!in_array($action, self::ACL_VALID_CRUD_ACTIONS)) {
            return false;
            throw new \Exception(sprintf('%s does not support "%s" action.', get_class($acl->getResource()), $action));
        }

        if($acl->getPermissionSet()->getUserPermission($user)->get($action)->allowAll()) {
            return true;
        }

        if(!$member = $this->getMember(ResourceAclMember::class, $acl, $user)) {
            return false;
        }
        if ($this->isMemberGranted($member, $requiredRole)) {
            return true;
        }
        return $member->getPermission()->get($action)->allowAll();
    }

    public function canManageAcl(AclEntityInterface $acl, string $attribute): bool
    {
        if(!$user = $this->getUser()) {
            return false;
        }

        $action = 'manage_acl';
        $resourceClass = $acl->getResource()::class;
        $requiredRole = $this->getDefaultRole($action, $resourceClass);

        if ($this->_canManageAcl($acl, $user, $requiredRole, $attribute, $action)) {
            return true;
        }

        if (is_subclass_of($resourceClass, 'HasContainerAclInterface')){
            // Check if parent has access.  Only pertains currently to vendorUsers.
            return $this->_canManageAcl($acl->getResource()->getContainer()->getResourceAcl(), $user, $requiredRole, $attribute, $action);
        }

        return false;
    }

    private function _canManageAcl(?ResourceAclInterface $acl, BasicUserInterface $user, string $requiredRole, string $attribute, string $action): bool
    {
        if ($this->isGranted($requiredRole)) {
            return true;
        }

        if(is_null($acl)) {
            // Must be a newly created entity and $acl will not be populated until prePersist.
            return false;
        }

        if(!$member = $this->getMember(ResourceAclMember::class, $acl, $user)) {
            return false;
        }
        if ($this->isMemberGranted($member, $requiredRole)) {
            return true;
        }
        if (!$member->getManageAcl()) {
            return false;
        }
        return match ($attribute) {
            'ACL_READ_ACL' => $member->getPermission()->getRead()->allowAll(),
            'ACL_WRITE_ACL' => $member->getPermission()->getUpdate()->allowAll(),
            default => throw new \Exception(sprintf('Attribute %s is not supportted', $attribute)),
        };
    }

    // Doesn't check if a member because there can't be any yet.
    public function canCreateAclEntity(string $resourceClass): bool
    {
        if(!$user = $this->getUser()) {
            return false;
        }

        $requiredRole = $this->getDefaultRole('create', $resourceClass);

        if ($this->isGranted($requiredRole)) {
            return true;
        }

        if (is_subclass_of($resourceClass, 'HasContainerAclInterface')){
            // Check if parent has access.  Only pertains currently to vendorUsers.
            if ($this->isGranted($requiredRole)) {
                return true;
            }
        }

        return false;
    }

    /*
    //Symfony\Component\Security\Core\Authorization\Voter\RoleVoter
    private function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !str_starts_with($attribute, $this->prefix)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            foreach ($roles as $role) {
                if ($attribute === $role) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }
        return $result;
    }
    */
}
