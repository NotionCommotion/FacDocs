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

use App\Entity\Project\Project;
use App\Entity\Asset\Asset;
use App\Entity\DocumentGroup\DocumentGroup;
use App\Entity\User\TenantUser;
use App\Entity\Organization\Vendor;
use App\Entity\User\VendorUser;
use App\Entity\Specification\CustomSpecification;
use App\Entity\Archive\Template;
use App\Entity\Archive\Archive;

use App\Entity\User\UserInterface;
use App\Repository\Acl\ResourceAclMemberRepository;
use App\Provider\AclMemberProvider;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
//use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\NotExposed;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

// Evidently, internally needs a generic GET endpoint.
//#[NotExposed]
#[ApiResource(
    operations: [new Get(), new Put(), new Delete()],//, new Patch()],
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'ID is a user/acl composite ID and is primarily used to support internal requests.  Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    security: "is_granted('ACL_MANAGE_PROJECT', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/projects/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: Project::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_PROJECT', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/assets/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: Asset::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_ASSET', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/document_groups/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: DocumentGroup::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_DOC_GROUP', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/tenant_users/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: TenantUser::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_TENANT_USER', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/vendors/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: Vendor::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_VENDOR', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/vendor_users/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: VendorUser::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_VENDOR_USER', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/custom_specifications/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: CustomSpecification::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_CUST_SPEC', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/templates/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: Template::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_MANAGE_TEMPLATE', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/archives/{id}/users/{userId}/resourceMember',
    uriVariables: ['id' => new Link(fromClass: Archive::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [
        new Get(security: "is_granted('ACL_READ_ACL', object)"),
        new Put(), new Post(), new Delete()
    ],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    //status: 200,
    security: "is_granted('ACL_WRITE_ACL', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]

#[ORM\Entity(repositoryClass: ResourceAclMemberRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'acl_id'])] // Maybe not necessary?
class ResourceAclMember extends AbstractAclMember implements HasRolesInterface
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['acl_member:read'])]
    protected AbstractResourceAcl $acl;

    //#[Assert\Choice(choices: TenantUser::ROLES, multiple: true, multipleMessage: 'Choose a valid role.')]
    #[ORM\Column(type: 'json')]
    #[ApiProperty(openapiContext: ['example' => ["ROLE_MANAGE_PROJECT"]])]
    #[Groups(['acl_member:read', 'acl_member:write'])]
    private array $roles = [];

    // Just used for FK constraints.  Called by event subscriber and has only a single setRoleConstraint() method.
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'resource_member_role_constraint')]
    #[ORM\JoinColumn(name: 'acl_id', referencedColumnName: 'acl_id', onDelete: 'CASCADE')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Collection $roleConstraints;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['acl_member:read', 'acl_member:write'])]
    #[ApiProperty(openapiContext: ['example' => false])]
    private ?bool $manageAcl = false;

    public function __construct
    (
        HasResourceAclInterface $resource,

        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'resourceAclMembers')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['acl_member:read'])]
        protected UserInterface $user,
    )
    {
        $this->roleConstraints = new ArrayCollection();
        parent::__construct($resource->getResourceAcl());
    }

    public function getRoles(): array
    {
        return $this->roles?$this->roles:['ROLE_USER'];
    }
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
    public function addRole(string $role): self
    {
        return $this->setRoles(array_unique(array_merge($this->roles, [$role])));
    }
    public function removeRole(string $role): self
    {
        return $this->setRoles(array_diff($this->roles, [$role]));
    }

    public function setRoleConstraint(Collection $roles): self
    {
        $this->roleConstraints = $roles;
        return $this;
    }
    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_merge(parent::debug($follow, $verbose, $hide), ['roles' => $this->roles]);
    }

    public function getManageAcl(): ?bool
    {
        return $this->manageAcl;
    }

    public function setManageAcl(bool $manageAcl): self
    {
        $this->manageAcl = $manageAcl;

        return $this;
    }

    public static function normalize(AclPermission $permission):array
    {
        return AbstractResourceAcl::normalize($permission);
    }

    public static function denormalize(array $permission):AclPermission
    {
        return AbstractResourceAcl::denormalize($permission);
    }
}
