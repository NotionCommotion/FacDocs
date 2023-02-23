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

namespace App\Entity\Acl;
use App\Entity\User\BasicUserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use App\Exception\InvalidAclPermissionException;

#[ORM\Embeddable]
class AclPermissionSet// implements \JsonSerializable  //For unknown reason, JsonSerializable causes error in AclPermissionNormalizer
{
    public function __construct(
        #[ORM\Column(type: 'acl_permission')]
        #[Groups(['acl:read', 'acl_member:read', 'acl:write', 'acl_member:write'])]
        #[SerializedName('tenantUser')]
        private AclPermission $tenantUserPermission,

        #[ORM\Column(type: 'acl_permission')]
        #[Groups(['acl:read', 'acl_member:read', 'acl:write', 'acl_member:write'])]
        #[SerializedName('tenantMember')]
        private AclPermission $tenantMemberPermission,

        #[ORM\Column(type: 'acl_permission')]
        #[Groups(['acl:read', 'acl_member:read', 'acl:write', 'acl_member:write'])]
        #[SerializedName('vendorUser')]
        private AclPermission $vendorUserPermission,

        #[ORM\Column(type: 'acl_permission')]
        #[Groups(['acl:read', 'acl_member:read', 'acl:write', 'acl_member:write'])]
        #[SerializedName('vendorMember')]
        private AclPermission $vendorMemberPermission
    )
    {
    }

    public static function createFromArray(array $tenantUser, array $tenantMember, array $vendorUser, array $vendorMember):self
    {
        return new self(AclPermission::createFromArray($tenantUser), AclPermission::createFromArray($tenantMember), AclPermission::createFromArray($vendorUser), AclPermission::createFromArray($vendorMember));
    }
    public static function createFromAssociateArray(array $permissions):self
    {
        return self::createFromArray($permissions['tenantUser']??[], $permissions['tenantMember']??[], $permissions['vendorUser']??[], $permissions['vendorMember']??[]);
    }

    public function validate():void
    {
        $errors = [];
        foreach(['tenantUserPermission', 'tenantMemberPermission', 'vendorUserPermission', 'vendorMemberPermission'] as $permission) {
            try {
                $this->$permission->validate();
            }
            catch (InvalidAclPermissionException $e) {
                $errors[] = $permission.' - '.$e->getMessage();
            }
        }
        if($errors) {
            throw new InvalidAclPermissionException(implode(', ', $errors).': '.$this->toCrudString());
        }
    }

    // Used to clone a project
    public function assimilate(self $permissionSetPrototype):self
    {
        $this->tenantUserPermission = $permissionSetPrototype->getTenantUserPermission();
        $this->tenantMemberPermission = $permissionSetPrototype->getTenantMemberPermission();
        $this->vendorUserPermission = $permissionSetPrototype->getVendorUserPermission();
        $this->vendorMemberPermission = $permissionSetPrototype->getVendorMemberPermission();
        return $this;
    }

    public function getTenantUserPermission(): AclPermission
    {
        return $this->tenantUserPermission;
    }
    public function setTenantUserPermission(AclPermission $permission): self
    {
        $this->tenantUserPermission = $permission;
        return $this;
    }

    public function getTenantMemberPermission(): AclPermission
    {
        return $this->tenantMemberPermission;
    }
    public function setTenantMemberPermission(AclPermission $permission): self
    {
        $this->tenantMemberPermission = $permission;
        return $this;
    }

    public function getVendorUserPermission(): AclPermission
    {
        return $this->vendorUserPermission;
    }
    public function setVendorUserPermission(AclPermission $permission): self
    {
        $this->vendorUserPermission = $permission;
        return $this;
    }

    public function getVendorMemberPermission(): AclPermission
    {
        return $this->vendorMemberPermission;
    }
    public function setVendorMemberPermission(AclPermission $permission): self
    {
        $this->vendorMemberPermission = $permission;
        return $this;
    }

    public function getUserPermission(BasicUserInterface $user): AclPermission
    {
        return $user->isTenantUser()?$this->getTenantUserPermission():$this->getVendorUserPermission();
    }
    public function getMemberPermission(BasicUserInterface $user): AclPermission
    {
        return $user->isTenantUser()?$this->getTenantMemberPermission():$this->getVendorMemberPermission();
    }

    public function setToNoAccess():self
    {
        $this->tenantUserPermission->setToNoAccess();
        $this->tenantMemberPermission->setToNoAccess();
        $this->vendorUserPermission->setToNoAccess();
        $this->vendorMemberPermission->setToNoAccess();
        return $this;
    }

    public function toCrudString(bool $readUpdateOnly=false):string
    {
        return sprintf('t.u: %s t.m: %s v.u: %s v.m: %s', $this->tenantUserPermission->toCrudString($readUpdateOnly), $this->tenantMemberPermission->toCrudString($readUpdateOnly), $this->vendorUserPermission->toCrudString($readUpdateOnly), $this->vendorMemberPermission->toCrudString($readUpdateOnly));
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return [
            'tenant' => [
                'user' => $this->tenantUserPermission->debug($follow, $verbose, $hide),
                'member' => $this->tenantMemberPermission->debug($follow, $verbose, $hide),
            ],
            'vendor' => [
                'user' => $this->vendorUserPermission->debug($follow, $verbose, $hide),
                'member' => $this->vendorMemberPermission->debug($follow, $verbose, $hide),
            ]
        ];
    }

    //public function jsonSerialize():mixed
    public function toArray(bool $readUpdateOnly=false):mixed
    {
        return [
            'tenant' => ['user' => $this->tenantUserPermission->toArray($readUpdateOnly),'member' => $this->tenantMemberPermission->toArray($readUpdateOnly),],
            'vendor' => ['user' => $this->vendorUserPermission->toArray($readUpdateOnly),'member' => $this->vendorMemberPermission->toArray($readUpdateOnly),]
        ];
    }
    public function __clone()
    {
        $this->tenantUserPermission = clone $this->tenantUserPermission;
        $this->tenantMemberPermission = clone $this->tenantMemberPermission;
        $this->vendorUserPermission = clone $this->vendorUserPermission;
        $this->vendorMemberPermission = clone $this->vendorMemberPermission;
    }
}
