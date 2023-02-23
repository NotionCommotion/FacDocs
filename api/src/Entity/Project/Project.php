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

namespace App\Entity\Project;

use App\Doctrine\Filters\ResourceMemberFilter;
use App\Entity\Acl\HasDocumentAclInterface;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\DocumentAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasDocumentAclTrait;
use App\Entity\Acl\HasResourceAclTrait;
// use App\Entity\Acl\AclDefaultRole;
use App\Entity\Archive\Archive;
use App\Entity\Asset\AssetInterface;
use App\Entity\Asset\Asset;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\Specification\SpecificationInterface;
use App\Entity\Document\Document;
use App\Entity\User\AbstractUser;
use App\Entity\Interfaces\UpdateDefaultValuesInterface;
use Money\Money;
use App\Entity\Interfaces\HasCollectionInterface;
use App\Entity\Interfaces\IsClonableInterface;
use App\Entity\ListRanking\RankedListAttribute;
use App\Entity\Location\Location;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\MultiTenenacy\HasPublicIdTrait;
use App\Entity\Organization\TenantInterface;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\Project\ProjectRepository;
use App\Provider\ProjectProvider;
use App\Processor\AclAddMemberProcessor;
use App\Processor\CloneProjectProcessor;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
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
use DateTime;

//#[AclDefaultRole(create: 'ROLE_MANAGE_PROJECT', read: 'ROLE_READ_PROJECT', update: 'ROLE_UPDATE_PROJECT', delete: 'ROLE_MANAGE_PROJECT', manageAcl: 'ROLE_MANAGE_ACL_PROJECT')]
#[ApiResource(
    operations: [
        new GetCollection(),    // Security handled by ResourceAclExtension
        new Get(
            security: "is_granted('ACL_RESOURCE_READ', object)",
        ),
        new Put(
            //denormalizationContext: ["groups" => ["project:write", "location:write"]],
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        /*
        // Why not allow Patch?
        new Patch(
            //denormalizationContext: ["groups" => ["project:write", "location:write"]],
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        */
        new Delete(
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
        ),
        new Post(
            // How do I create a new Project manually instead of having the Symfony serializer do so?
            //provider: AclResourceProvider::class,
            denormalizationContext: ["groups" => ["project:write", "location:write"]],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['project:write', 'location:write']],
    normalizationContext: ['groups' => ['project:read', 'location:read', 'identifier:read', 'public_id:read', 'user_action:read', 'ranked_list:read']]
)]

#[ApiResource(
    uriTemplate: '/projects/{id}/clone',
    uriVariables: ['id' => new Link(fromClass: Project::class, fromProperty: 'id')],
    provider: ProjectProvider::class,
    //status: 200,
    securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
    operations: [
        new Post(
            input: false,   //Don't send body
            processor: CloneProjectProcessor::class,
            deserialize: false,
        ),
    ],
    denormalizationContext: ['groups' => ['project:write', 'location:write']],
    normalizationContext: ['groups' => ['project:read', 'location:read', 'identifier:read', 'public_id:read', 'user_action:read', 'ranked_list:read']]
)]

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\UniqueConstraint(columns: ['public_id', 'tenant_id'])]
#[ORM\AssociationOverrides(
    [new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'projects')],
)]
#[ORM\UniqueConstraint(name: 'project_name_unique', columns: ['name', 'tenant_id'])]
#[ORM\UniqueConstraint(name: 'project_id_unique', columns: ['project_id', 'tenant_id'])]
#[ORM\Index(name: 'idx_project_us_state', columns: ['location_state'])] // See Location embeddable
#[RankedListAttribute]
#[ApiFilter(ResourceMemberFilter::class)]
#[ApiFilter(SearchFilter::class, properties: ['projectStage' => 'exact'])]
//#[ApiFilter(\App\Filter\SingleRandomRowTestFilter::class)]
class Project implements HasUlidInterface, BelongsToTenantInterface, HasCollectionInterface, IsClonableInterface, HasResourceAclInterface, HasDocumentAclInterface, HasPublicIdInterface, UpdateDefaultValuesInterface
{
    use HasUlidTrait, HasDocumentAclTrait, HasResourceAclTrait {
        HasDocumentAclTrait::debug insteadof HasUlidTrait, HasResourceAclTrait;
        HasDocumentAclTrait::setId insteadof HasResourceAclTrait;
    }
    use BelongsToTenantTrait, HasPublicIdTrait {
        HasPublicIdTrait::setTenant insteadof BelongsToTenantTrait;
    }
    use UserActionTrait;
    
    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?ProjectResourceAcl $resourceAcl = null;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?ProjectDocumentAcl $documentAcl = null;

    #[Groups(['project:read', 'project:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    private string $name;
    
    #[Groups(['project:read', 'project:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $projectId = null;
    
    #[Groups(['project:read', 'user:read', 'project:write'])]
    #[ORM\Column(type: 'boolean')]
    #[ApiFilter(BooleanFilter::class)]
    private bool $isActive = true;
    
    #[Groups(['project:read', 'user:read', 'project:write'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $startAt = null;
    
    #[Groups(['project:read', 'user:read', 'project:write'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $completeAt = null;

    #[Groups(['project:read', 'user:read', 'project:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    
    /**
     * @var Archive[]|Collection|ArrayCollection
     */
    #[Groups(['project:read', 'project:write'])]
    #[ORM\OneToMany(targetEntity: Archive::class, mappedBy: 'project', orphanRemoval: true)]
    private Collection $archives;
    
    // #[RankedListAttribute]
    // #[RankedListAttribute(method: 'getSomeOtherName')]
    #[Groups(['project:read', 'project:write'])]
    #[ORM\ManyToOne(targetEntity: ProjectStage::class)]
    //#[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'project_stages/construction'])]
    private ProjectStage $projectStage;
    
    #[Groups(['project:read', 'project:write'])]
    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'assets/00000000000000000000000000'], readableLink: false, writableLink: false)]
    private ?AssetInterface $defaultAsset=null;
    
    #[Groups(['project:read', 'project:write'])]
    #[ORM\ManyToOne]
    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'], readableLink: false, writableLink: false)]
    private ?AbstractSpecification $defaultSpecification=null;

    #[Groups(['project:read'])]
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Document::class, orphanRemoval: true)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $documents;
    
    #[Groups(['project:read', 'project:write'])]
    #[ORM\Embedded(class: Location::class)]
    private Location $location;
    
    #[Groups(['project:read', 'project:write'])]
    #[ORM\Embedded(class: Money::class)]
    private ?Money $budget = null;
    
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectTeamMember::class)]
    #[Groups(['project:read', 'project:write'])]
    private Collection $projectTeamMembers;

    #[Groups(['project:read', 'project:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $projectTeamDescription = null;

    public function __construct()
    {
        $this->archives = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->projectTeamMembers = new ArrayCollection();
        $this->budget = Money::USD(0);   //new Money(0);
        $this->location = new Location();
    }

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new ProjectResourceAcl($entity);
    }
    static public function createDocumentAcl(HasDocumentAclInterface $entity): DocumentAclInterface
    {
        return new ProjectDocumentAcl($entity);
    }

    // Override parent which just sets ID to null.
    public function __clone()
    {
        $this->id=null;
        $this->publicId=null;
        $this->resourceAcl=null;
        $this->aclResourceHash = null;
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
            $document->setProject($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documents->removeElement($document) && $document->getProject() === $this) {
            $document->setProject(null);
        }

        return $this;
    }

    public function getPublicIdIndex(): ?string
    {
        return 'project';
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function setProjectId(?string $projectId): self
    {
        $this->projectId = $projectId;

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

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getStartAt(): ?DateTime
    {
        return $this->startAt;
    }

    public function setStartAt(?DateTime $startAt): self
    {
        $this->startAt = $startAt;

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

    /**
     * @return Collection|Archive[]
     */
    public function getArchives(): Collection
    {
        return $this->archives;
    }

    public function addArchive(Archive $archive): self
    {
        if (!$this->archives->contains($archive)) {
            $this->archives[] = $archive;
            $archive->setProject($this);
        }

        return $this;
    }

    public function removeArchive(Archive $archive): self
    {
        if (!$this->archives->removeElement($archive)) {
            return $this;
        }
        if ($archive->getProject() !== $this) {
            return $this;
        }
        $archive->setProject(null);

        return $this;
    }

    public function getProjectStage(): ?ProjectStage
    {
        return $this->projectStage;
    }

    public function setProjectStage(?ProjectStage $projectStage): self
    {
        $this->projectStage = $projectStage;

        return $this;
    }

    //Called by UpdateDefaultValuesSubscriber
    public function updateDefaultValues(): void
    {
        if (!$this->getDefaultAsset()){
            $this->setDefaultAsset($this->getTenant()->getRootAsset());
        }
        if (!$this->getDefaultSpecification()){
            $this->setDefaultSpecification($this->getTenant()->getPrimarySpecification());
        }
    }

    public function getDefaultAsset(): ?AssetInterface
    {
        return $this->defaultAsset;
    }

    public function setDefaultAsset(?AssetInterface $defaultAsset): self
    {
        $this->defaultAsset = $defaultAsset;

        return $this;
    }

    public function getDefaultSpecification(): ?SpecificationInterface
    {
        return $this->defaultSpecification;
    }

    public function setDefaultSpecification(?SpecificationInterface $defaultSpecification): self
    {
        $this->defaultSpecification = $defaultSpecification;

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

     public function getBudget():Money
    {
        return $this->budget;
    }

    public function setBudget(Money $money): self
    {
        $this->budget = $money;
        return $this;
    }

    /**
     * @return Collection<int, ProjectTeamMember>
     */
    public function getProjectTeamMembers(): Collection
    {
        return $this->projectTeamMembers;
    }

    public function addProjectTeamMember(ProjectTeamMember $projectTeamMember): self
    {
        if (!$this->projectTeamMembers->contains($projectTeamMember)) {
            $this->projectTeamMembers->add($projectTeamMember);
            $projectTeamMember->setProjectTeam($this);
        }

        return $this;
    }

    public function removeProjectTeamMember(ProjectTeamMember $projectTeamMember): self
    {
        if ($this->projectTeamMembers->removeElement($projectTeamMember)) {
            // set the owning side to null (unless already changed)
            if ($projectTeamMember->getProjectTeam() === $this) {
                $projectTeamMember->setProjectTeam(null);
            }
        }

        return $this;
    }

    public function getProjectTeamDescription(): ?string
    {
        return $this->projectTeamDescription;
    }

    public function setProjectTeamDescription(?string $projectTeamDescription): self
    {
        $this->projectTeamDescription = $projectTeamDescription;

        return $this;
    }
}
