<?php

declare(strict_types=1);

namespace App\Security\Service;

use App\Entity\Acl\ManagedByAclInterface;
use App\Entity\User\BasicUserInterface;
use App\Entity\Acl\DocumentAclInterface;
use App\Entity\Acl\DocumentAclMember;
use App\Entity\Acl\ResourceAclMember;
use App\Entity\Acl\AclPermissionEnum;
use App\Entity\Acl\AbstractDocumentAcl;
use App\Entity\Project\ProjectDocumentAcl;
use App\Entity\Document\Document;
use App\Entity\Document\Media;
use App\Repository\Acl\DocumentAclRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

final class DocumentAclService extends AbstractAclService
{
    private const MEDIA_STALE_SECONDS_DURATION = 5;  // Change to about 10 seconds after testing.

    private const ACL_VALID_CRUD_ACTIONS = ['create', 'read', 'update', 'delete'];

    public function __construct(DocumentAclRepository $aclRepository, AccessRoleService $accessRoleService, Security $security, EntityManagerInterface $entityManager)
    {
        parent::__construct($aclRepository, $accessRoleService, $security, $entityManager);
    }

    // action is create, read, update, delete
    public function canPerformCrud(ManagedByAclInterface $subject, string $action): bool
    {
        if(!$user = $this->getUser()) {
            return false;
        }

        if(!$media = $subject->getMedia()) {
            // Validation should be performed elsewhere.  Temp solution.
            return false;
        }

        $action = strtolower($action);
        
        /*
        if ($action === 'create' && $this->isOrphanMedia($subject->getMedia(), $user)) {
            // Not even tenant admins will have access to orphaned media and they will automatically be deleted by the cleaner.
            // TBD whether this should be performed for all actions.
            return false;
        }
        */

        $requiredRole = $this->getDefaultRole($action, get_class($subject));
        if ($this->isGranted($requiredRole)) {
            return true;
        }

        // Future.  Allow other types such as Assets and DocumentGroups.
        
        if(!in_array($action, self::ACL_VALID_CRUD_ACTIONS)) {
            throw new Exception(sprintf('%s does not support "%s" action.', get_class($subject), $action));
        }
        
        $project = $subject->getProject();
        
        // TBD whether roles should be used with document ACL members.
        if($member = $this->getMember(ResourceAclMember::class, $project->getResourceAcl(), $user)) {
            if ($this->isMemberGranted($member, $requiredRole)) {
                return true;
            }
        }

        // Prevent user from adding media to a document where they don't have access to the media.
        // Medias collection is not writable so no need to check.
        if(!$this->isOwnedNotStaleMedia($media, $user) && !$this->getEntityManager()->getRepository(Document::class)->userHasAccessToMedia($user, $media, $requiredRole)) {
            return false;
        }

        $acl = $project->getDocumentAcl();
        if($this->_canPerformCrud($user, $subject, $acl->getPermissionSet()->getUserPermission($user)->get($action), $action === 'create')) {
            return true;
        }
        if(!$member = $this->getMember(DocumentAclMember::class, $acl, $user)) {
            return false;
        }
        return $this->_canPerformCrud($user, $subject, $member->getPermission()->get($action), $action === 'create');
    }

    private function _canPerformCrud(BasicUserInterface $user, Document $item, AclPermissionEnum $permission, bool $create): bool
    {
        //$this->_echo(sprintf('%s allowAll: %s allowOwner: %s ownedByUser: %s allowCoworker: %s ownedByCoworker: %s allowVendor: %s isVendorUser: %s, isTenantUser: %s, create: %s', __METHOD__, $permission->allowAll()?'t':'f', $permission->allowOwner()?'t':'f', $create?'N/A':($item->ownedByUser($user)?'t':'f'), $permission->allowCoworker()?'t':'f', $create?'N/A':($item->ownedByCoworker($user)?'t':'f'), $permission->allowVendor()?'t':'f', $create?'N/A':($item->getOwner()->isVendorUser()?'t':'f'), $user->isTenantUser()?'t':'f', $create?'t':'f'));
        return $create
        ?$permission->allowAll()
        : 
        $permission->allowAll()
        ||
        $permission->allowOwner() && $item->ownedByUser($user)
        ||
        $permission->allowCoworker() && ($item->ownedByUser($user) || $item->ownedByCoworker($user))
        ||
        $permission->allowVendor() && $item->getOwner()->isVendorUser() && $user->isTenantUser();
    }

    private function isOrphanMedia(Media $media, BasicUserInterface $user): bool
    {
        return $this->isOwnedNotStaleMedia($media, $user)
        ?false
        :$this->getEntityManager()->getRepository($media::class)->isOrphan($media);
    }

    public function userHasAccessToMedia(Media $media): bool
    {
        if(!$user = $this->getUser()) {
            return false;
        }

        /*
        // Only do this check if canPerformCrud() is changed to do similarily
        if ($this->isOrphanMedia($media, $user)) {
            // Not even tenant admins will have access to orphaned media and they will automatically be deleted by the cleaner.
            return false;
        }
        */
        $requiredRole = $this->getDefaultRole('read', $media::class);
        if ($this->isGranted($requiredRole)) {
            return true;
        }
        return $this->getEntityManager()->getRepository(Document::class)->userHasAccessToMedia($user, $media, $requiredRole);
    }

    private function isOwnedNotStaleMedia(Media $media, BasicUserInterface $user): bool
    {
        // Return true if the user owns the media and it is not stale.
        return $user->isSame($media->getCreateBy()) && ((new \DateTime)->getTimestamp()-$media->getCreateAt()->getTimestamp())<self::MEDIA_STALE_SECONDS_DURATION;
    }
}
