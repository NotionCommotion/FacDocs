<?php

declare(strict_types=1);

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;
use App\Entity\Acl\HasAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\AclMemberInterface;
use App\Entity\Acl\ResourceAclMember;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\AclInterface;
use Symfony\Component\Uid\Ulid;

class ResourceAuthorizationStatus extends SimpleAuthorizationStatus
{
    protected const ACL_VALID_CRUD_ACTIONS = ['create'=>201, 'read'=>200, 'update'=>200, 'delete'=>204];

    private AclPermissionSet $resourcePermissionSet;
    private ?array $resourceMemberRoles=null;
    private ?AclPermission $resourceMemberPermission=null;

    public function __construct(private string $action, UserInterface $user, private HasAclInterface $resource, int $anticipatedStatusCode, bool $isAuthorized)
    {
        parent::__construct($user, $anticipatedStatusCode, $isAuthorized, $resource::class, $resource->getId());

        $this->action = strtolower($this->action);
        if(!isset(self::ACL_VALID_CRUD_ACTIONS[$this->action])) {
            throw new \Exception(sprintf('%s does not support "%s" action.', get_class($resource), $this->action));
        }

        $this->resourcePermissionSet = clone $resource->getResourceAcl()->getPermissionSet();
        if($resourceAclMember = $this->getResourceMember()) {
            $this->resourceMemberRoles = $resourceAclMember->getRoles();
            $this->resourceMemberPermission = clone $resourceAclMember->getPermission();
        }
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResource():HasAclInterface
    {
        return $this->resource;
    }

    public function getResourcePermissionSet(): AclPermissionSet
    {
        return $this->resourcePermissionSet;
    }

    public function getResourceMember(): ?ResourceAclMember
    {
        return $this->getMember($this->resource->getResourceAcl());
    }

    public function getMemberRoles(): ?array
    {
        return $this->resourceMemberRoles;
    }

    public function getResourceMemberPermission(): ?AclPermission
    {
        return $this->resourceMemberPermission;
    }

    public function getMessage(): string
    {
        $memberMsg = $this->resourceMemberPermission
        ?sprintf('and member roles %s and member permission %s',  implode(', ', $this->resourceMemberRoles), $this->resourceMemberPermission->toCrudString(true))
        :'who is not a member';

        return sprintf('%s with role %s %s attempts to %s a %s with permission policy %s',
            $this->getShortName($this->getUser()),
            implode(', ', $this->getUser()->getRoles()),
            $memberMsg,
            $this->getAction(),
            $this->getShortName($this->getResourceClass()),
            $this->resourcePermissionSet->toCrudString(true)
        );
    }

    public function getData():array
    {
        $data = parent::getData();

        $data['acl'] = [
            'action' => $this->action,
            'resourcePermissionSet' => $this->resourcePermissionSet->toArray(true),
            'documentPermissionSet' => null,
            'member'=> null,
        ];
        if(!is_null($this->resourceMemberPermission)) {
            $data['acl']['member'] = [
                'roles' => $this->resourceMemberRoles,
                'resourcePermission' => $this->resourceMemberPermission->toArray(true),
                'documentPermission' => null,
            ];
        }
        return $data;
    }

    protected function _getDebugMessage():array
    {
        $msg = parent::_getDebugMessage();

        $msg[] = sprintf('%s %s %s', $this->getShortName($this->getUser()::class), $this->getAction(), $this->getShortName($this->getResourceClass()));
        $msg[] = sprintf('Resource ACL: %s', $this->resourcePermissionSet->toCrudString(true));
        if(!is_null($this->resourceMemberPermission)) {
            $msg[] = sprintf('Resource member roles: %s', implode(', ', $this->resourceMemberRoles));
            $msg[] = sprintf('Resource member ACL: %s', $this->resourceMemberPermission->toCrudString(true));
        }
        return $msg;
    }

    public function toArray(bool $readUpdateOnly=true): array
    {
        return array_merge(parent::toArray(false), [
            'action'=>$this->action,
            'resourceAcl'=>$this->resource->debug(),
        ]);
    }

    protected function getMember(AclInterface $acl): ?AclMemberInterface
    {
        return $acl->getMemberByUser($this->getUser());
    }
}
