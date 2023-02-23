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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Acl\AclUserInterface;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasContainerAclInterface;
// use App\Entity\Acl\AclDefaultRole;
use App\Model\Config\VendorConfig;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Organization\VendorInterface;
use App\Entity\Organization\TenantInterface;
use App\Repository\User\VendorUserRepository;
use App\Provider\CurrentUserProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_VENDOR_USER', read: 'ROLE_READ_VENDOR_USER', update: 'ROLE_UPDATE_VENDOR_USER', delete: 'ROLE_MANAGE_VENDOR_USER', manageAcl: 'ROLE_MANAGE_ACL_VENDOR_USER')]
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
            denormalizationContext: ['groups' => ['user:write', 'new_user:write']],
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
    uriTemplate: '/vendor_users/self',
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
#[ORM\Entity(repositoryClass: VendorUserRepository::class)]
class VendorUser extends AbstractUser implements VendorUserInterface, BelongsToTenantInterface, HasPublicIdInterface, AclUserInterface, HasResourceAclInterface //, HasContainerAclInterface
{
    use HasResourceAclTrait;

    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }
    
    final public const ROLES = ['ROLE_VENDOR_USER', 'ROLE_VENDOR_ADMIN', 'ROLE_VENDOR_SUPER'];

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?VendorUserResourceAcl $resourceAcl = null;

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new VendorUserResourceAcl($entity);
    }

    public function getContainer():HasResourceAclInterface
    {
        return $this->organization;
    }
    
    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_merge(parent::debug($follow, $verbose, $hide), ['tenant'=>$this->getTenant()?$this->getTenant()->debug($follow>0?--$follow:$follow):'NULL']);
    }

    public function setOrganization(OrganizationInterface $organization): self
    {
        if (!$organization instanceof VendorInterface) {
            throw new \Exception(sprintf('VendorUsers may only belong to a Vendor organization and not a %s.', get_class($organization)));
        }
        $this->setTenant($organization->getTenant());

        return parent::setOrganization($organization);
    }

    public function getOrganizationsTenant(): TenantInterface
    {
        // Used to work with fixures.
        return $this->organization->getTenant();
    }
    
    public function getPublicIdIndex(): ?string
    {
        return 'vendor-user';
    }

    public function getConfig(): VendorConfig
    {
        return new VendorConfig($this, $this->getOrganization());
    }

    public function getAclUserPermission(AclPermissionSet $permissionSet): AclPermission
    {
        return $permissionSet->getVendorUserPermission();
    }
    public function getAclMemberPermission(AclPermissionSet $permissionSet): AclPermission
    {
        return $permissionSet->getVendorMemberPermission();
    }
}
