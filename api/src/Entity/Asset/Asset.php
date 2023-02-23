<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Root Asset Persister.
 * - Cannot be deleted.
 * - Decommisioned cannot be false.
 */

/**
 * Question - Should asset default permissions pulled from the parent asset or the root asset?  Think it needs to be the root since assets can have many parents.
 */
/*
https://dwbi1.wordpress.com/2017/10/18/hierarchy-with-multiple-parents/
http://blog.chrisadamson.com/2012/05/recursive-hierarchies-and-bridge-tables.html
*/
// Consider adding some HierarchyRelationshipInterface and HierarchyCollectionInterface and appropriate traits to support.
// Consider changing many-to-many to Asset and Document to a middle entity so that other data could be added (i.e. changedBy user)

declare(strict_types=1);

namespace App\Entity\Asset;

use App\Doctrine\Filters\ResourceMemberFilter;
// use App\Entity\Acl\AclDefaultRole;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Document\Document;
use App\Entity\Interfaces\RequiresAdditionalValidationInterface;
use App\Entity\Organization\TenantInterface;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Processor\ParentAssetAddChildAssetProcessor;
use App\Processor\ParentAssetRemoveChildAssetProcessor;
use App\Processor\AssetAddDocumentProcessor;
use App\Processor\AssetRemoveDocumentProcessor;
use App\Provider\AssetProvider;
use App\Provider\AssetChildrenProvider;
use App\Provider\AssetParentsProvider;
use App\Entity\Location\Location;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Repository\Asset\AssetRepository;

use Money\Money;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
// use Symfony\Component\Serializer\Annotation\MaxDepth;
use Exception;

//#[AclDefaultRole(create: 'ROLE_MANAGE_ASSET', read: 'ROLE_READ_ASSET', update: 'ROLE_UPDATE_ASSET', delete: 'ROLE_MANAGE_ASSET', manageAcl: 'ROLE_MANAGE_ACL_ASSET')]
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
            denormalizationContext: ["groups" => ["asset:write", "location:write"]],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['asset:write', 'location:write']],
    normalizationContext: [
        'groups' => ['asset:read', 'location:read', 'identifier:read', 'public_id:read', 'user_action:read', 'ranked_list:read'],
        'enable_max_depth' => true
    ]
)]

#[ApiResource(
    uriTemplate: '/assets/{id}/children',
    //uriVariables: ['id' => new Link(fromClass: Asset::class, fromProperty: 'children')],
    provider: AssetChildrenProvider::class,
    normalizationContext: [
        'groups' => ['asset:read', 'identifier:read', 'public_id:read', 'user_action:read', 'location:read'],
        'enable_max_depth' => true
    ],
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'Retrieves the collection of an Asset\'s children Asset resources.',
                'description' => 'Retrieves the collection of an Asset\'s children Asset resources',
            ]
        ),
    ]
)]

#[ApiResource(
    uriTemplate: '/assets/{id}/parents',
    //uriVariables: ['id' => new Link(fromClass: Asset::class, fromProperty: 'parents')],
    provider: AssetParentsProvider::class,
    normalizationContext: [
        'groups' => ['asset:read', 'identifier:read', 'public_id:read', 'user_action:read', 'location:read'],
        'enable_max_depth' => true
    ],
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'Retrieves the collection of an Asset\'s parent Asset resources.',
                'description' => 'Retrieves the collection of an Asset\'s parent Asset resources',
            ]
        ),
    ]
)]

#[ApiResource(
    uriTemplate: '/assets/{id}/children/{childId}',
    uriVariables: [
        'id' => new Link(fromClass: Asset::class, fromProperty: 'id'),
        'childId' => new Link(fromClass: Asset::class, fromProperty: 'id')
    ],
    provider: AssetProvider::class,
    //status: 200,
    operations: [
        new Delete(
            processor: ParentAssetRemoveChildAssetProcessor::class,
            deserialize: false,
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
            openapiContext: [
                'summary' => 'Remove a child Asset Resource from a parent Asset container',
                'description' => 'Remove a child Asset Resource from a parent Asset container',
            ]
        ),
        new Post(
            processor: ParentAssetAddChildAssetProcessor::class,
            input: false,   //Don't send body
            deserialize: false,
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
            openapiContext: [
                'summary' => 'Add a child Asset Resource to a parent Asset resource',
                'description' => 'Add a child Asset Resource to a parent Asset resource',
                'requestBody' => []
            ]
        ),
    ]
)]

#[ApiResource(
    // Provider, voter, etc should be passed Asset and not Document so that the WHERE client could be created on Asset.
    uriTemplate: '/assets/{id}/documents/{documentId}',
    uriVariables: [
        'id' => new Link(fromClass: Asset::class, fromProperty: 'id'),
        'documentId' => new Link(fromClass: Document::class, fromProperty: 'id')
    ],
    provider: AssetProvider::class,
    //status: 200,
    denormalizationContext: ['groups' => ['asset:write']],
    normalizationContext: ['groups' => ['asset:read', 'identifier:read', 'user_action:read']],
    operations: [
        new Delete(
            processor: AssetRemoveDocumentProcessor::class,
            deserialize: false,
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
            openapiContext: [
                'summary' => 'Remove a Document Resource from an Asset container',
                'description' => 'Remove a Document Resource from an Asset container',
            ]
        ),
        new Post(
            processor: AssetAddDocumentProcessor::class,
            input: false,   //Don't send body
            deserialize: false,
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
            openapiContext: [
                'summary' => 'Add a Document Resource to an Asset container',
                'description' => 'Add a Document Resource to an Asset container',
                'requestBody' => []
            ]
        ),
    ]
)]


#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'assets')])]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'name'])]
#[ORM\UniqueConstraint(columns: ['public_id', 'tenant_id'])]
#[ORM\Index(name: 'idx_asset_us_state', columns: ['location_state'])] // See Location embeddable
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(ResourceMemberFilter::class)]
class Asset implements HasUlidInterface, BelongsToTenantInterface, AssetInterface, HasPublicIdInterface, RequiresAdditionalValidationInterface, HasResourceAclInterface
{
    use HasUlidTrait, HasResourceAclTrait {
        HasResourceAclTrait::debug insteadof HasUlidTrait;
    }
    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }
    use UserActionTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?AssetResourceAcl $resourceAcl = null;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'assets')]
    #[ORM\JoinTable(name: 'asset_have_documents')]
    //#[ApiProperty(readableLink: false, writableLink: false, security: "is_granted('ACL_DOCUMENT_READ', object)")]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $documents;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\ManyToMany(targetEntity: Asset::class, inversedBy: 'parents')]
    #[ORM\JoinTable(name: 'asset_parents_have_children')]
    #[ORM\JoinColumn(name: 'child_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $children;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\ManyToMany(targetEntity: Asset::class, mappedBy: 'children')]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $parents;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    private ?string $name = null;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\Embedded(class: Money::class)]
    //#[ApiProperty(openapiContext: ['example' => '["amount"=>"0","currency"=>"USD"]'])]
    private Money $cost;

    // Used for ragged hierarchies.  Not currenty used.
    // private $level;
    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[ApiProperty(openapiContext: ['example' => 'false'])]
    private bool $decommissioned = false;

    // TBD whether root asset should use tenant's location.
    #[Groups(['asset:read', 'asset:write'])]
    #[ORM\Embedded(class: Location::class)]
    private ?Location $location = null;

    public function __construct(
        #[ORM\Column(type: 'boolean')]
        #[Groups(['asset:read'])]
        private bool $isRoot=false
    )
    {
        $this->documents = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->cost = Money::USD(0);   //new Money(0);
        $this->location = new Location();
    }

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new AssetResourceAcl($entity);
    }

    public function isRoot(): bool
    {
        return $this->isRoot;
    }
    // Used so it is serialized.
    public function getIsRoot(): bool
    {
        return $this->isRoot;
    }

    //LifecycleCallback
    #[ORM\PreRemove]
    public function preventRemoval(): void
    {
        if($this->isRoot && $this->getTenant()) {
            // If tenant is being deleted, okay to delete root.
            throw new Exception('Root asset may not be deleted');
        }
    }

    public function getPublicIdIndex(): ?string
    {
        return 'asset';
    }

    /**
     * @return Collection|Asset[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Asset $asset): self
    {
        if (!$this->children->contains($asset)) {
            $this->children[] = $asset;
        }

        return $this;
    }

    public function removeChild(Asset $asset): self
    {
        $this->children->removeElement($asset);

        return $this;
    }

    /**
     * @return Collection|Asset[]
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function addParent(Asset $asset): self
    {
        if (!$this->parents->contains($asset)) {
            $this->parents[] = $asset;
            $asset->addChild($this);
        }

        return $this;
    }

    public function removeParent(Asset $asset): self
    {
        if ($this->parents->removeElement($asset)) {
            $asset->removeChild($this);
        }

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        $this->documents->removeElement($document);

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

    public function getDecommissioned(): bool
    {
        return $this->decommissioned;
    }

    public function setDecommissioned(bool $decommissioned): self
    {
        $this->decommissioned = $decommissioned;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getCost():Money
    {
        return $this->cost;
    }

    public function setCost(Money $money): self
    {
        $this->cost = $money;
        return $this;
    }
}
