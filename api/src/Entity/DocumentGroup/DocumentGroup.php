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

namespace App\Entity\DocumentGroup;

use App\Doctrine\Filters\ResourceMemberFilter;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasResourceAclTrait;
use App\Entity\Document\Document;
use App\Entity\Interfaces\HasCollectionInterface;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Organization\TenantInterface;
use App\Entity\Trait\UserAction\UserActionTrait;
// use App\Entity\Acl\AclDefaultRole;
use App\Processor\DocumentGroupAddDocumentProcessor;
use App\Processor\DocumentGroupRemoveDocumentProcessor;
use App\Provider\DocumentGroupProvider;
use App\Repository\DocumentGroup\DocumentGroupRepository;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

//#[AclDefaultRole(create: 'ROLE_MANAGE_DOC_GROUP', read: 'ROLE_READ_DOC_GROUP', update: 'ROLE_UPDATE_DOC_GROUP', delete: 'ROLE_MANAGE_DOC_GROUP', manageAcl: 'ROLE_MANAGE_ACL_DOC_GROUP')]
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
            denormalizationContext: ['groups' => ['document_group:write', 'location:write']],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['document_group:write', 'location:write']],
    normalizationContext: [
        'groups' => ['document_group:read', 'location:read', 'identifier:read', 'public_id:read', 'user_action:read', 'ranked_list:read'],
        'enable_max_depth' => true
    ]
)]

#[ApiResource(
    uriTemplate: '/document_groups/{id}/documents/{documentId}',
    uriVariables: ['id' => new Link(fromClass: DocumentGroup::class, fromProperty: 'id'), 'documentId' => new Link(fromClass: Document::class, fromProperty: 'id')],
    provider: DocumentGroupProvider::class,
    //status: 200,
    operations: [
        new Delete(
            processor: DocumentGroupRemoveDocumentProcessor::class,
            deserialize: false,
            openapiContext: [
                'summary' => 'Remove a Document Resource from a DocumentGroup container',
                'description' => 'Remove a Document Resource from a DocumentGroup container',
            ]
        ),
        new Post(
            processor: DocumentGroupAddDocumentProcessor::class,
            input: false,   //Don't send body
            deserialize: false,
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
            openapiContext: [
                'summary' => 'Add a Document Resource to a DocumentGroup container',
                'description' => 'Add a Document Resource to a DocumentGroup container',
                'requestBody' => []
            ]
        ),
    ]
)]

#[ORM\Entity(repositoryClass: DocumentGroupRepository::class)]
#[ORM\AssociationOverrides(
    [new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'documentGroups')],
)]
#[ORM\UniqueConstraint(name: 'document_group_name_unique', columns: ['name', 'tenant_id'])]
#[ApiFilter(ResourceMemberFilter::class)]
class DocumentGroup implements HasUlidInterface, BelongsToTenantInterface, HasCollectionInterface, HasResourceAclInterface
{
    use HasUlidTrait, HasResourceAclTrait {
        HasResourceAclTrait::debug insteadof HasUlidTrait;
    }
    use BelongsToTenantTrait;
    use UserActionTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?DocumentGroupResourceAcl $resourceAcl = null;

    #[Groups(['document_group:read', 'document_group:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    private string $name;

    #[Groups(['document_group:read', 'user:read', 'document_group:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(['document_group:read', 'document_group:write'])]
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'documentGroups')]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new DocumentGroupResourceAcl($entity);
    }

    /**
     * @return Collection<int, Document>
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
}
