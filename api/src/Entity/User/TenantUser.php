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

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use App\Model\Config\TenantConfig;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Acl\AclUserInterface;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
// use App\Entity\Acl\AclDefaultRole;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Organization\TenantInterface;
use App\Entity\Organization\Tenant;
use App\Repository\User\TenantUserRepository;
use App\Provider\CurrentUserProvider;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_TENANT_USER', read: 'ROLE_READ_TENANT_USER', update: 'ROLE_UPDATE_TENANT_USER', delete: 'ROLE_MANAGE_TENANT_USER', manageAcl: 'ROLE_MANAGE_ACL_TENANT_USER')]
#[ApiResource(
    operations: [
        new GetCollection(),    // Security handled by ResourceAclExtension
        new Get(
            security: "is_granted('ACL_RESOURCE_READ', object)",
        ),
        new Put(
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        new patch(
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        new Delete(
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
        ),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['user:write']],
    normalizationContext: ['groups' => ['user:read', 'identifier:read', 'public_id:read', 'user_action:read']]
)]

/*
// Needs work!
#[ApiResource(
    uriTemplate: '/tenant_users/self',
    provider: CurrentUserProvider::class,
    normalizationContext: ['groups' => ['self_user:read', 'identifier:read', 'public_id:read'],],
    denormalizationContext: ['groups' => ['self_user:write'],],
    //security: "is_granted('ROLE_TENANT_ADMIN') or object == user",
    operations: [
        new Get(),
        // Why is the user not being populated?
        //new Put(), new Patch(),
    ]
)]
*/
//#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'users')])]
#[ORM\Entity(repositoryClass: TenantUserRepository::class)]
class TenantUser extends AbstractUser implements TenantUserInterface, BelongsToTenantInterface, HasPublicIdInterface, AclUserInterface, HasResourceAclInterface
{
    use HasResourceAclTrait;

    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }

    final public const ROLES = ['ROLE_TENANT_USER', 'ROLE_TENANT_ADMIN', 'ROLE_TENANT_SUPER'];

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?TenantUserResourceAcl $resourceAcl = null;

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new TenantUserResourceAcl($entity);
    }

    // Override parent to work with fixures.
    public function setTenant(TenantInterface $tenant): self
    {
        $this->setOrganization($tenant);
        $this->tenant = $tenant;
        $tenant->setEntityPublicId($this);

        return $this;
    }
    public function setOrganization(OrganizationInterface $organization): self
    {
        if (!$organization instanceof TenantInterface) {
            throw new \Exception(sprintf('TenantUsers may only belong to a Tenant organization and not a %s.', get_class($organization)));
        }

        return parent::setOrganization($organization);
    }

    public function getPublicIdIndex(): ?string
    {
        return 'tenant-user';
    }

    public function getConfig(): TenantConfig
    {
        return new TenantConfig($this, $this->getOrganization());
    }

    public function getAclUserPermission(AclPermissionSet $permissionSet): AclPermission
    {
        return $permissionSet->getTenantUserPermission();
    }
    public function getAclMemberPermission(AclPermissionSet $permissionSet): AclPermission
    {
        return $permissionSet->getTenantMemberPermission();
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_diff_key(parent::debug($follow, $verbose, $hide), ['organization'=>null]);
    }
}
