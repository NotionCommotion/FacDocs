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

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Ulid;
use App\Entity\User\UserInterface;

#[ORM\MappedSuperclass, ORM\HasLifecycleCallbacks]
abstract class AbstractAcl implements AclInterface, \Stringable
{
    // Following are used for collection requests so bitwide operator need not be used and member role need not be checked.
    #[ORM\Column]
    protected AclPermissionEnum $tenantReadPermission;
    #[ORM\Column]
    protected AclPermissionEnum $vendorReadPermission;

    // Same ID as the resourceId
    #[ApiProperty(identifier: false)] // Should I use $acl or $resource as identifier?
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'ulid', unique: true)]
    //#[Groups(['acl:read'])]
    protected Ulid $id;

    public function __construct(HasResourceAclInterface $entity,)
    {
        // Consider not making the ACL share the same PK.
        if(!$id = $entity->getId()) {
            $id = new Ulid;
            $entity->setId($id);
        }
        $this->id = $id;
        $this->resource = $entity;
        $this->updateReadPermission();
        $this->members = new ArrayCollection();
    }

    //LifecycleCallback
    #[ORM\PreUpdate]
    public function updateReadPermission(): void
    {
        $this->tenantReadPermission = $this->permissionSet->getTenantUserPermission()->getRead();
        $this->vendorReadPermission = $this->permissionSet->getVendorUserPermission()->getRead();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id->toRfc4122();
    }

    public function getResource(): ?HasResourceAclInterface
    {
        return $this->resource;
    }

    // Used to clone a project
    public function assimilate(AclInterface $aclPrototype):self
    {
        $this->permissionSet->assimilate($aclPrototype->getPermissionSet());
        $this->tenantReadPermission = $aclPrototype->getTenantReadPermission();
        $this->vendorReadPermission = $aclPrototype->getVendorReadPermission();
        $this->members = $aclPrototype->getMembers();
        return $this;
    }
    // Only used to clone
    public function getTenantReadPermission():AclPermissionEnum
    {
        return $this->tenantReadPermission;
    }
    public function getVendorReadPermission():AclPermissionEnum
    {
        return $this->vendorReadPermission;
    }

    public function getPermissionSet(): AclPermissionSet
    {
        return $this->permissionSet;
    }
    public function setPermissionSet(AclPermissionSet $permissionSet): self
    {
        $this->permissionSet = $permissionSet;
        return $this;
    }

    /**
     * @return Collection<int, AclMemberInterface>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(AclMemberInterface $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            if($this !== $member->getAcl()){
                throw new \Exception(sprintf('ACL should have already been set in $member\'s constructor. Existing: %s New: %s', json_encode($this->debug()), json_encode($member->getAcl()->debug())));
            }
            // $member->setAcl($this);
        }

        return $this;
    }

    public function removeMember(AclMemberInterface $member): self
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getAcl() === $this) {
                // How should this be handled?
                $member->setAcl(null);
            }
        }

        return $this;
    }

    // Temp hack.
    public function getMemberByUser(UserInterface $user): ?AbstractAclMember
    {
        foreach($this->members as $member) {
            if($user === $member->getUser()) {
                return $member;
            }
        }
        return null;
    }
    public function removeUserAsMember(UserInterface $user): self
    {
        if($member = $this->getMemberByUser($user)) {
            $this->removeMember($member);
        }
        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return [
            'id' => $this->id,
            'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL',
            'permissionSet' => $this->permissionSet->debug($follow, $verbose, $hide),
            'members' => array_map(function($member) use ($follow, $verbose, $hide) {return $member->debug($follow, $verbose, $hide);}, $this->members->toArray()),
            'tenantReadPermission' => isset($this->tenantReadPermission)?$this->tenantReadPermission->name:null,
            'vendorReadPermission' => isset($this->vendorReadPermission)?$this->vendorReadPermission->name:null,
            'class' => get_class($this)
        ];
    }
}
