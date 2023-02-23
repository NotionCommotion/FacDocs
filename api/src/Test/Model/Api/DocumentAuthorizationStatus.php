<?php

declare(strict_types=1);

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;
use App\Entity\Document\Document;
use App\Entity\Acl\HasDocumentAclInterface;
use App\Entity\Acl\DocumentAclMember;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\AclPermissionEnum;
use App\Entity\Acl\DocumentAclInterface;
use App\Security\Service\AccessRoleService;

class DocumentAuthorizationStatus extends ResourceAuthorizationStatus
{
    private AclPermissionSet $documentPermissionSet;
    private ?AclPermission $documentMemberPermission=null;
    private bool $isOwner;
    private bool $isCoworker;

    public function __construct(string $action, UserInterface $user, HasDocumentAclInterface $project, private Document $document, AccessRoleService $accessRoleService, ?int $anticipatedStatusCode=null)
    {
        // Doesn't test for stale media

        $action = strtolower($action);
        $isAuthorized = $this->_isAuthorized($action, $user, $project->getDocumentAcl(), $document, $accessRoleService);
        $anticipatedStatusCode = $anticipatedStatusCode??$isAuthorized?self::ACL_VALID_CRUD_ACTIONS[$action]:403;
        parent::__construct($action, $user, $project, $anticipatedStatusCode, $isAuthorized);

        $this->isOwner = $document->ownedByUser($user);
        $this->isCoworker = $document->ownedByCoworker($user);

        $this->documentPermissionSet = clone $project->getDocumentAcl()->getPermissionSet();
        if($documentAclMember = $this->getDocumentMember()) {
            $this->documentMemberPermission = clone $documentAclMember->getPermission();
        }
    }

    public function getDocumentPermissionSet(): AclPermissionSet
    {
        return $this->documentPermissionSet;
    }

    public function getDocumentMember(): ?DocumentAclMember
    {
        return $this->getMember($this->getResource()->getDocumentAcl());
    }

    public function getDocumentMemberPermission(): ?AclPermission
    {
        return $this->documentMemberPermission;
    }

    public function isOwner():bool
    {
        return $this->isOwner;
    }
    public function isCoworker():bool
    {
        return $this->isCoworker;
    }

    public function getMessage(): string
    {
        $memberMsg = $this->documentMemberPermission
        ?sprintf('and member permission %s', $this->documentMemberPermission->toCrudString(false))
        :'who is not a member';

        $docMsg = $this->getAction()==='create'
        ?''
        :sprintf(' owned by %s with permissions %s',
            match (true) {
                $this->isOwner => 'themself',
                $this->isCoworker =>'a coworker',
                default =>'another organization'
            },
            $this->documentPermissionSet->toCrudString(false)
        );

        return sprintf('%s with role %s %s attempts to %s a document%s',
            $this->getShortName($this->getUser()),
            implode(', ', $this->getUser()->getRoles()),
            $memberMsg,
            $this->getAction(),
            $docMsg
        );
    }

    public function getData():array
    {
        $data = parent::getData();
        $data['user']['isDocumentOwner'] = $this->isOwner;
        $data['user']['isDocumentCoworker'] = $this->isCoworker;
        $data['acl']['documentPermissionSet'] = $this->documentPermissionSet->toArray(false);
        $data['acl']['document'] = [
            'id'=>$this->document->getId()->toBase32(),
            'ownerId'=>$this->document->getOwner()->getId()->toBase32(),
            'organizationId'=>$this->document->getOwner()->getOrganization()->getId()->toBase32(),
            'organizationType'=>$this->document->getOwner()->getOrganization()->getType()->name,
        ];

        if(!is_null($this->documentMemberPermission)) {
            $data['acl']['member']['documentPermission'] = $this->documentMemberPermission->toArray(false);
        }
        return $data;
    }

    protected function _getDebugMessage():array
    {
        $msg = parent::_getDebugMessage();
        $msg[] = sprintf('isOwner: %s isCoworker: %s', $this->isOwner()?'Yes':'No', $this->isCoworker()?'Yes':'No');
        $msg[] = sprintf('Document ACL: %s', $this->documentPermissionSet->toCrudString(false));
        if($member = $this->getDocumentMember()) {
            $msg[] = sprintf('Document member ACL: %s', $member->getPermission()->toCrudString(false));
        }
        return $msg;
    }

    public function toArray(bool $readUpdateOnly=true): array
    {
        $data = parent::toArray(false);
        $data['document'] = $this->document->debug();
        $data['user']['isOwner'] = $this->isOwner();
        $data['user']['isCoworker'] = $this->isCoworker();
        return $data;
    }

    private function _isAuthorized(string $action, UserInterface $user, DocumentAclInterface $documentAcl, Document $document, AccessRoleService $accessRoleService):bool
    {
        // Doesn't test for stale media
        //print_r($documentAcl->debug());
        $requiredRole = $accessRoleService->getDefaultRole($document::class, $action);
        if ($accessRoleService->isUserGranted($user, $requiredRole)) {
            return true;
        }
        if($this->_canPerformCrud($user, $document, $documentAcl->getPermissionSet()->getUserPermission($user)->get($action), $action === 'create')) {
            return true;
        }
        if(!$member = $documentAcl->getMemberByUser($user)) {
            return false;
        }
        return $this->_canPerformCrud($user, $document, $member->getPermission()->get($action), $action === 'create');
    }

    private function _canPerformCrud(UserInterface $user, Document $item, AclPermissionEnum $permission, bool $create): bool
    {
        //$this->_echo(sprintf('%s allowAll: %s allowOwner: %s ownedByUser: %s allowCoworker: %s ownedByCoworker: %s allowVendor: %s isVendorUser: %s, isTenantUser: %s', __METHOD__, $this->getDocumentAclPermissionSet()->getUserPermission($this->user)->allowAll()?'t':'f', $this->getDocumentAclPermissionSet()->getUserPermission($this->user)->allowOwner()?'t':'f', $this->action==='create'?'N/A':($item->ownedByUser($this->user)?'t':'f'), $this->getDocumentAclPermissionSet()->getUserPermission($this->user)->allowCoworker()?'t':'f', $this->action==='create'?'N/A':($item->ownedByCoworker($this->user)?'t':'f'), $this->getDocumentAclPermissionSet()->getUserPermission($this->user)->allowVendor()?'t':'f', $this->action==='create'?'N/A':($item->getOwner()->isVendorUser()?'t':'f'), $this->user->isTenantUser()?'t':'f'));
        //$this->_echo(sprintf('%s perm: %s create: %s ownedByUser: %s ownedByCoworker: %s isVendorUser: %s isTenantUser: %s'.PHP_EOL, __METHOD__, $permission->name, $create?'T':'F', $item->ownedByUser($user)?'T':'F', $item->ownedByCoworker($user)?'T':'F', $item->getOwner()->isVendorUser()?'T':'F', $user->isTenantUser()?'T':'F'));

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
}
