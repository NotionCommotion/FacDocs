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

namespace App\Entity\Organization;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
// use App\Entity\Acl\AclDefaultRole;
use App\Entity\Interfaces\UpdateDefaultValuesInterface;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Entity\User\UserInterface;
use App\Entity\User\VendorUserInterface;
use App\Repository\Organization\VendorRepository;
use App\Provider\CurrentOrganizationProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_VENDOR', read: 'ROLE_READ_VENDOR', update: 'ROLE_UPDATE_VENDOR', delete: 'ROLE_MANAGE_VENDOR', manageAcl: 'ROLE_MANAGE_ACL_VENDOR')]
#[ApiResource(
    operations: [
        new GetCollection(),    // Security handled by ResourceAclExtension
        new Get(
            security: "is_granted('ACL_RESOURCE_READ', object)",
        ),
        new Put(
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        new Delete(
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
        ),
        new Post(
            denormalizationContext: ['groups' => ['vendor:write', 'organization:write', 'location:write']],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['vendor:write', 'organization:write', 'location:write']],
    normalizationContext: ['groups' => ['vendor:read', 'organization:read', 'identifier:read', 'public_id:read', 'user_action:read', 'location:read']]
)]

/*
// Needs work!
#[ApiResource(
    uriTemplate: '/vendors/self',
    provider: CurrentOrganizationProvider::class,
    normalizationContext: ['groups' => ['self_org:read', 'identifier:read', 'location:read', 'public_id:read'],],
    denormalizationContext: ['groups' => ['self_org:write', 'location:write'],],
    security: "is_granted('ROLE_TENANT_ADMIN', object)",
    operations: [
        new Get(),
        // Why is the user not being populated?
        //new Put(), new Patch(),
    ]
)]
*/
#[ORM\Entity(repositoryClass: VendorRepository::class)]
#[ORM\UniqueConstraint(columns: ['public_id', 'tenant_id'])]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'vendors')])]
class Vendor extends AbstractOrganization implements VendorInterface, BelongsToTenantInterface, HasPublicIdInterface, HasResourceAclInterface, UpdateDefaultValuesInterface
{
    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }
    use HasResourceAclTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?VendorResourceAcl $resourceAcl = null;

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new VendorResourceAcl($entity);
    }

    //Called by UpdateDefaultValuesSubscriber
    public function updateDefaultValues(): void
    {
        // Specification is required.  Use tenant's if not provided.
        if (!$this->getPrimarySpecification()) {
            $this->setPrimarySpecification($this->getTenant()->getPrimarySpecification());
        }
    }

    public function getPublicIdIndex(): ?string
    {
        return 'vendor';
    }

    public function addUser(UserInterface $user): self
    {
        if (!$user instanceof VendorUserInterface) {
            throw new \Exception(sprintf('User must be a VendorUser to belong to a Vendor. %s given.', get_class($user)));
        }
        return parent::addUser($user);
    }

    public function getType(): OrganizationType
    {
        return OrganizationType::Vendor;
    }

    // Override so that trait isn't used
    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return parent::debug($follow, $verbose, $hide);
    }
}
