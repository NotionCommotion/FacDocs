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

namespace App\Entity\Specification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Interfaces\RequiresAdditionalValidationInterface;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
// use App\Entity\Acl\AclDefaultRole;
// use ApiPlatform\Metadata\ApiFilter;
// use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
// use ApiPlatform\Serializer\Filter\PropertyFilter;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\Specification\CustomSpecificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_CUST_SPEC', read: 'ROLE_READ_CUST_SPEC', update: 'ROLE_UPDATE_CUST_SPEC', delete: 'ROLE_MANAGE_CUST_SPEC', manageAcl: 'ROLE_MANAGE_ACL_CUST_SPEC')]
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
            denormalizationContext: ["groups" => ["specification:write"]],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: [
        'groups' => ['specification:write']
    ],
    normalizationContext: [
        'groups' => ['specification:read', 'identifier:read', 'public_id:read', 'user_action:read'],
        'enable_max_depth' => true
    ],
    paginationItemsPerPage: 20
)]
#[ORM\UniqueConstraint(columns: ['public_id', 'tenant_id'])]
#[ORM\UniqueConstraint(columns: ['name', 'tenant_id'])]
#[ORM\Entity(repositoryClass: CustomSpecificationRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'customSpecifications')])]
class CustomSpecification extends AbstractSpecification implements SpecificationInterface, BelongsToTenantInterface, RequiresAdditionalValidationInterface, HasPublicIdInterface, HasResourceAclInterface
{
    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }
    use HasResourceAclTrait;
    use UserActionTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?CustomSpecificationResourceAcl $resourceAcl = null;

    // Methods and $children defined in parent.
    #[Groups(['specification:read', 'specification:write'])]
    #[ORM\ManyToOne(targetEntity: AbstractSpecification::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'], readableLink: false, writableLink: false)]
    protected ?AbstractSpecification $parent = null;
    
    //Unique applied at table level with tenantId.
    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['specification:read', 'specification:write'])]
    private ?string $name = null;
    
    #[Groups(['specification:read', 'specification:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    
    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new CustomSpecificationResourceAcl($entity);
    }

    public function getPublicIdIndex(): ?string
    {
        return 'cust-spec';
    }
    public function getParent(): ?AbstractSpecification
    {
        return $this->parent;
    }
    public function setParent(?AbstractSpecification $specification): self
    {
        $this->parent = $specification;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
