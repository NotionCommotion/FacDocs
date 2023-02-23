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

namespace App\Entity\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\DownloadController;
use App\Entity\Asset\Asset;
use App\Entity\Acl\ManagedByAclInterface;
use App\Entity\DocumentGroup\DocumentGroup;
use App\Entity\ListRanking\RankedListAttribute;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Organization\TenantInterface;
use App\Entity\Project\Project;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\User\UserInterface;
use App\Entity\User\BasicUserInterface;
use App\Entity\Trait\UserAction\UserActionTrait;
// use App\Entity\Acl\AclDefaultRole;
use App\Processor\DocumentAddAssetProcessor;
use App\Processor\DocumentRemoveAssetProcessor;
use App\Provider\AssetProvider;
use App\Processor\DocumentAddMediaProcessor;
use App\Processor\DocumentRemoveMediaProcessor;
use App\Provider\DocumentProvider;
use App\Provider\DocumentMediaProvider;
use App\Provider\MediaDocumentsProvider;
use App\Repository\Document\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Ulid;
//use Symfony\Component\Serializer\Annotation\Ignore;
use Gedmo\Mapping\Annotation as Gedmo;

//#[AclDefaultRole(create: 'ROLE_MANAGE_DOCUMENT', read: 'ROLE_READ_DOCUMENT', update: 'ROLE_UPDATE_DOCUMENT', delete: 'ROLE_MANAGE_DOCUMENT')]
#[ApiResource(
    operations: [
        new GetCollection(),    // Security handled by DocumentAclExtension
        new Get(
            security: "is_granted('ACL_DOCUMENT_READ', object)",
        ),
        new Get(
            uriTemplate: '/documents/{id}/download',
            security: "is_granted('ACL_DOCUMENT_READ', object)",
            controller: DownloadController::class,
            openapiContext: ['summary' => 'Download Document Resource', 'description' => 'Download a Document resource']
        ),
        new Put(
            security: "is_granted('ACL_DOCUMENT_UPDATE', object)",
        ),
        new Delete(
            security: "is_granted('ACL_DOCUMENT_DELETE', object)",
        ),
        new Post(
            denormalizationContext: ['groups' => ['document:write', 'organization:write', 'location:write']],
            // need to use securityPostDenormalize since otherwise voter will not be passed an object.
            securityPostDenormalize: "is_granted('ACL_DOCUMENT_CREATE', object)",
        )
    ],
    types: ['http://schema.org/Document'],
    denormalizationContext: ['groups' => ['document:write']],
    normalizationContext: ['groups' => ['document:read', 'identifier:read', 'user_action:read']]
)]

#[Put(
    uriTemplate: '/documents/{id}/projects/{projectId}',
    uriVariables: ['id' => new Link(fromClass: Document::class, fromProperty: 'id'), 'projectId' => new Link(fromClass: Project::class, fromProperty: 'id')],
    provider: DocumentProvider::class,
    processor: DocumentMoverProcessor::class,
    security: "is_granted('ACL_DOCUMENT_MOVE', object)",
    denormalizationContext: ['groups' => ['document:write']],
    openapiContext: ['summary' => 'Move a document to a new project.', 'description' => 'Move a document to a new project.']
)]
#[Post(
    uriTemplate: '/documents/{id}/clone/projects/{projectId}',
    uriVariables: ['id' => new Link(fromClass: Document::class, fromProperty: 'id'), 'projectId' => new Link(fromClass: Project::class, fromProperty: 'id')],
    provider: DocumentCloneProvider::class,
    processor: DocumentMoverProcessor::class,
    security: "is_granted('ACL_DOCUMENT_MOVE', object)",
    denormalizationContext: ['groups' => ['document:write']],
    openapiContext: ['summary' => 'Add a copy of a document to a new project.', 'description' => 'Add a copy of a document to a new project.']
)]

#[ApiResource(
    uriTemplate: '/documents/{id}/media/{mediaId}',
    uriVariables: [
        'id' => new Link(fromClass: Document::class, fromProperty: 'id'),
        'mediaId' => new Link(fromClass: Media::class, fromProperty: 'id')
    ],
    provider: DocumentProvider::class,
    //status: 200,
    denormalizationContext: ['groups' => ['document:write']],
    normalizationContext: ['groups' => ['document:read', 'identifier:read', 'user_action:read']],
    operations: [
        new Delete(
            processor: DocumentRemoveMediaProcessor::class,
            deserialize: false,
            security: "is_granted('ACL_DOCUMENT_UPDATE', object)",
            openapiContext: [
                'summary' => 'Remove a Media Resource from an Document container.',
                'description' => 'Remove a Media Resource from an Document container.',
            ]
        ),
        new Post(
            processor: DocumentAddMediaProcessor::class,
            input: false,   //Don't send body
            deserialize: false,
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_DOCUMENT_UPDATE', object)",
            openapiContext: [
                'summary' => 'Add a Media Resource to an Document container.',
                'description' => 'Add a Media Resource to an Document container.',
                'requestBody' => []
            ]
        ),
    ]
)]

#[ApiResource(
    uriTemplate: '/documents/{id}/media.{_format}',
    //uriVariables: ['id' => new Link(fromClass: \App\Entity\Document\Document::class, identifiers: ['id'])],
    //status: 200,
    types: ['http://schema.org/Document'],
    // security: "is_granted('ACL_DOCUMENT_READ', object)",   // Access control performed by DocumentAclExtension.
    provider: DocumentMediaProvider::class,
    normalizationContext: ['groups' => ['media_object:read', 'identifier:read', 'user_action:read']],
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'Retrieves the collection of Media associated with a given Document.',
                'description' => 'Retrieves the collection of Media associated with a given Document.',
            ]
        ),
    ]
)]
#[ApiResource(
    uriTemplate: '/media/{id}/documents.{_format}',
    //uriVariables: ['id' => new Link(fromClass: \App\Entity\Document\Media::class, identifiers: ['id'])],
    //status: 200,
    types: ['http://schema.org/Document'],
    // security: "is_granted('ACL_DOCUMENT_READ', object)",   // Access control performed by DocumentAclExtension.
    provider: MediaDocumentsProvider::class,
    normalizationContext: ['groups' => ['document:read', 'user_action:read']],
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'Retrieves the collection of Documents which use a given Media.',
                'description' => 'Retrieves the collection of Documents which use a given Media.',
            ]
        ),
    ]
)]

#[ORM\Entity(repositoryClass: DocumentRepository::class), ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(columns: ['id'])]
#[RankedListAttribute]
class Document implements HasUlidInterface, BelongsToTenantInterface, DocumentInterface, DownloadableFileInterface, ManagedByAclInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;
    use UserActionTrait;

    // Future.  Ability to include media (file) when performing a POST request (add UploadableFileInterface)
    #[ORM\ManyToMany(targetEntity: Media::class)]
    #[Groups(['document:read'])]
    #[ApiProperty(
        //security: "is_granted('MEDIA_ADD', object)",  //Can't add here?
        readableLink: false, writableLink: false,
        openapiContext: ['example' => '["media/00000000000000000000000000"]']
    )]
    private Collection $medias;

    // Represents the "current" media for the document (version control)
    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(
        //security: "is_granted('MEDIA_ADD', object)",  //Can't add here?
        readableLink: false, writableLink: false,
        openapiContext: ['example' => 'media/00000000000000000000000000']
    )]
    #[Assert\NotNull]
    private ?Media $media = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups(['acl_admin:read', 'acl_admin:write', 'document:read'])]
    // Needed since this can be both in the parent and child.  How to fix?
    // #[MaxDepth(1)]    //Doesn't work!
    #[ApiProperty(readableLink: false, writableLink: false)]
    private ?UserInterface $owner=null;

    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: DocumentStage::class)]
    //#[ORM\JoinColumn(nullable: false)]
    #[Groups(['document:read', 'document:write'])]
    #[RankedListAttribute]
    #[ApiProperty(openapiContext: ['example' => 'document_stages/construction'])]
    private ?DocumentStage $documentStage = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(openapiContext: ['example' => 'projects/00000000000000000000000000'], readableLink: false, writableLink: false)]
    private ?Project $project = null;

    #[ORM\ManyToMany(targetEntity: Asset::class, mappedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(openapiContext: ['example' => '["assets/00000000000000000000000000"]'], readableLink: false, writableLink: false)]
    private Collection $assets;

    #[ORM\ManyToMany(targetEntity: DocumentGroup::class, mappedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(openapiContext: ['example' => '["documentGroup/00000000000000000000000000"]'], readableLink: false, writableLink: false)]
    private Collection $documentGroups;

    #[ORM\ManyToOne(targetEntity: AbstractSpecification::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'], readableLink: false, writableLink: false)]
    private ?AbstractSpecification $specification = null;

    #[ORM\ManyToOne(targetEntity: DocumentType::class)]
    //#[ORM\JoinColumn(nullable: false)]
    #[RankedListAttribute]
    #[Groups(['document:read', 'document:write'])]
    #[ApiProperty(openapiContext: ['example' => 'document_types/submittal'])]
    private ?DocumentType $documentType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $description = null;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->documentGroups = new ArrayCollection();
    }

    //#[Ignore] // Only necessary if no groups.
    public function getPhysicalMedia(): ?PhysicalMedia
    {
        return $this->media?->getPhysicalMedia();
    }

    public function hasPhysicalMedia(): bool
    {
        return $this->media?->hasPhysicalMedia();
    }

    public function getMediaType(): ?MediaType
    {
        return $this->media?->getMediaType();
    }

    public function getFilename(): ?string
    {
        return $this->media?->getFilename();
    }

    /**
     * @return Collection|Media[]
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): self
    {
        // Voter to ensure user has authority to add media
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
        }
        // New media will always be active.
        $this->media = $media;

        return $this;
    }

    public function removeMedia(Media $media): self
    {
        if ($this->medias->removeElement($media)) {
            // Use the most recent media as active (or null if none)
            $this->media = ($last = $this->medias->last()) ? $last : null;
        }

        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): self
    {
        // Add media should it not be current and set as active.
        return $this->addMedia($media);
    }

    //LifecycleCallback
    #[ORM\PrePersist]
    public function setFirstMedia(): void
    {
        if (($firstMedia = $this->getMedia()) !== null) {
            $this->addMedia($firstMedia);
        }
    }

    /**
     * @return Collection|Asset[]
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!$this->assets->contains($asset)) {
            $this->assets[] = $asset;
            $asset->addDocument($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): self
    {
        if ($this->assets->removeElement($asset)) {
            $asset->removeDocument($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentGroup>
     */
    public function getDocumentGroups(): Collection
    {
        return $this->documentGroups;
    }

    public function addDocumentGroup(DocumentGroup $documentGroup): self
    {
        if (!$this->documentGroups->contains($documentGroup)) {
            $this->documentGroups[] = $documentGroup;
            $documentGroup->addDocument($this);
        }

        return $this;
    }

    public function removeDocumentGroup(DocumentGroup $documentGroup): self
    {
        if ($this->documentGroups->removeElement($documentGroup)) {
            $documentGroup->removeDocument($this);
        }

        return $this;
    }

    public function getOwner(): ?UserInterface
    {
        return $this->owner;
    }

    public function setOwner(UserInterface $user): self
    {
        $this->owner = $user;
        
        return $this;
    }

    public function ownedByCoworker(BasicUserInterface $user): bool
    {
        return $this->owner?$user->isCoworker($this->owner):false;
    }
    public function ownedByUser(BasicUserInterface $user): bool
    {
        return $this->owner?$user->isSame($this->owner):false;
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

    public function getDocumentStage(): ?DocumentStage
    {
        return $this->documentStage;
    }

    public function setDocumentStage(?DocumentStage $documentStage): self
    {
        $this->documentStage = $documentStage;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getSpecification(): ?AbstractSpecification
    {
        return $this->specification;
    }

    public function setSpecification(?AbstractSpecification $specification): self
    {
        $this->specification = $specification;

        return $this;
    }

    public function getDocumentType(): ?DocumentType
    {
        return $this->documentType;
    }

    public function setDocumentType(?DocumentType $documentType): self
    {
        $this->documentType = $documentType;

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

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'owner'=>$this->owner?$this->owner->debug():null, 'class'=>get_class($this), 'media'=>$this->getMedia()->debug($follow, $verbose, $hide), 'medias'=>array_map(function($media)use($follow, $verbose, $hide){return $media->debug($follow, $verbose, $hide);}, $this->getMedias()->toArray())];
    }
}
