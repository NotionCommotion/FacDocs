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

use App\Entity\User\UserInterface;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Ulid;

#[ORM\MappedSuperclass]
abstract class AbstractAclMember implements AclMemberInterface
{
    #[ORM\Column(type: 'acl_permission')]
    #[Groups(['acl_member:read', 'acl_member:write'])]
    /*
    #[ApiProperty(openapiContext: [
        // See AclPermission why this isn't working.
        'example' => ['read'=>'ALL', 'update'=>'COWORKER', 'create'=>'NONE', 'delete'=>'OWNER'],
    ])]
    */
    protected AclPermission $permission;

    // Following are used for collection requests so bitwide operator need not be used and member role need not be checked.
    #[ORM\Column]
    private AclPermissionEnum $readPermission;

    public function __construct(AclInterface $acl,)
    {
        $this->acl = $acl;
        $this->permission = clone $this->acl->getPermissionSet()->getMemberPermission($this->user);
        $this->readPermission = $this->permission->getRead();
    }

    public function getResource(): HasAclInterface
    {
		return $this->getAcl()->getResource();
    }

    public function getPermission(): AclPermission
    {
        return $this->permission;
    }

    public function setPermission(AclPermission $permission): self
    {
        $this->permission = $permission;
        $this->readPermission = $this->permission->getRead();
        return $this;
    }

    public function getAcl(): AclInterface
    {
        return $this->acl;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return [
            'aclId' => $this->acl?$this->acl->getId():'NULL',
            'aclId-rfc4122' => $this->acl&&$this->acl->getId()?$this->acl->getId()->toRfc4122():'NULL',
            'aclPermissions' => $this->permission?$this->permission->debug($follow, $verbose, $hide):'NULL',
            'user' => $this->user?$this->user->debug($follow, $verbose, $hide):'NULL',
        ];
    }
}
