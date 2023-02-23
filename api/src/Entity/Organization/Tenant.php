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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Archive\Archive;
use App\Entity\Archive\Template;
use App\Entity\Asset\Asset;
use App\Entity\Asset\AssetInterface;
use App\Entity\Config\OverrideSetting;
use App\Entity\Document\MediaType;
use App\Entity\Document\SupportedMediaType;
use App\Entity\DocumentGroup\DocumentGroup;
use App\Entity\MultiTenenacy\HasPublicIdInterface;
use App\Entity\Project\Project;
use App\Entity\Specification\CustomSpecification;
use App\Entity\User\UserInterface;
use App\Entity\User\TenantUserInterface;
use App\Repository\Organization\TenantRepository;
use App\Provider\CurrentOrganizationProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        // Security for Get and GetCollection handled by ResourceAclExtension
        // Future allow system user to perform get collections, post and delete.
        new Get(),
        new Put(
            security: "is_granted('ACL_RESOURCE_UPDATE', object)",
        ),
        new GetCollection(
            security: "is_granted('ROLE_SYSTEM_USER')"
        ),
        new Post(
            security: "is_granted('ROLE_SYSTEM_USER')"
        ),
        new Delete(
            security: "is_granted('ROLE_SYSTEM_USER')"
        ),
    ],
    denormalizationContext: ['groups' => ['tenant:write', 'organization:write', 'location:write']],
    normalizationContext: ['groups' => ['tenant:read', 'organization:read', 'identifier:read', 'public_id:read', 'user_action:read', 'location:read']]
)]

// Needs work!
/*
#[ApiResource(
    uriTemplate: '/tenants/self',
    provider: CurrentOrganizationProvider::class,
    normalizationContext: ['groups' => ['self_org:read', 'identifier:read', 'location:read'],],
    denormalizationContext: ['groups' => ['self_org:write', 'location:write'],],
    operations: [
        new Get(),
        // Why is the user not being populated?
        //new Put(), new Patch(),
    ]
)]
*/
#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant extends AbstractOrganization implements TenantInterface
{
    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: VendorInterface::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $vendors;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $projects;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: DocumentGroup::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $documentGroups;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: Asset::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $assets;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: SupportedMediaType::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $supportedMediaTypes;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: CustomSpecification::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $customSpecifications;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: OverrideSetting::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    //#[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $overrideSettings;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: Template::class, mappedBy: 'tenant', cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $templates;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\OneToMany(targetEntity: Archive::class, mappedBy: 'tenant')]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Collection $archives;

    #[Groups(['tenant:read', 'tenant:write'])]
    #[ORM\Column(type: 'json')]
    private array $publicIdStack = [];

    #[Groups(['tenant:read'])]
    // Comment out next line to prevent errors when installing fixutres.
    #[ORM\OneToOne(targetEntity: Asset::class)] //, cascade: ['persist', 'remove'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private Asset $rootAsset;

    #[Groups(['acl_admin:read', 'acl_admin:write'])]
    #[ORM\Embedded(columnPrefix: 'resource_')]
    // Not sure if security is necessary since handled by TenantAccessControlContextBuilder (similar to AccessControlAttributeNormalizer)
    /*
    #[ApiProperty(security: "is_granted('ROLE_MANAGE_TENANT_ACL')",]
    */
    private AclPermissionSet $resourceAclPermissionSetPrototype;

    #[Groups(['acl_admin:read', 'acl_admin:write'])]
    #[ORM\Embedded(columnPrefix: 'document_')]
    // Not sure if security is necessary since handled by TenantAccessControlContextBuilder (similar to AccessControlAttributeNormalizer)
    /*
    #[ApiProperty(security: "is_granted('ROLE_MANAGE_TENANT_ACL')",]
    */
    private AclPermissionSet $documentAclPermissionSetPrototype;

    public function __construct()
    {
        parent::__construct();
        $this->vendors = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->documentGroups = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->supportedMediaTypes = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->archives = new ArrayCollection();
        $this->customSpecifications = new ArrayCollection();
        $this->overrideSettings = new ArrayCollection();

        $this->rootAsset = new Asset(true);
        $this->rootAsset->setTenant($this)->setName('Root')->setDescription('Root asset which all other assets are under');
        // Comment out next line to prevent errors when installing fixutres.
        $this->assets[] = $this->rootAsset;
        $this->resourceAclPermissionSetPrototype = new AclPermissionSet(AclPermission::createFromValue(), AclPermission::createFromValue(), AclPermission::createFromValue(), AclPermission::createFromValue());
        $this->documentAclPermissionSetPrototype = new AclPermissionSet(AclPermission::createFromValue(), AclPermission::createFromValue(), AclPermission::createFromValue(), AclPermission::createFromValue());
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_diff_key(array_merge(
            parent::debug($follow, $verbose, $hide),
            $verbose? [
                'resourceAclPermissionSetPrototype'=>$this->resourceAclPermissionSetPrototype->debug($follow, $verbose, $hide),
                'documentAclPermissionSetPrototype'=>$this->documentAclPermissionSetPrototype->debug($follow, $verbose, $hide),
            ]:[]
            ), array_flip($hide));
    }

    /**
     * Incrementing identifier per class.
     * Currently, use ULID so these are not needed.  See App\Entity\MultiTenenacy\HasPublicIdInterface.
     */
    public function setEntityPublicId(HasPublicIdInterface $hasPublicId): self
    {
        if (null === $hasPublicId->getPublicId()) {
            $publicIdIndex = $hasPublicId->getPublicIdIndex();
            $this->publicIdStack[$publicIdIndex] = ($this->publicIdStack[$publicIdIndex] ?? 0) + 1;
            $hasPublicId->setPublicId($this->publicIdStack[$publicIdIndex]);
        }

        return $this;
    }

    public function addUser(UserInterface $user): self
    {
        if (!$user instanceof TenantUserInterface) {
            throw new \Exception(sprintf('User must be a TenantUser to belong to a Tenant. %s given.', get_class($user)));
        }
        return parent::addUser($user);
    }

    /**
     * @return Collection|VendorInterface[]
     */
    public function getVendors(): Collection
    {
        return $this->vendors;
    }

    public function addVendor(VendorInterface $vendor): self
    {
        if (!$this->vendors->contains($vendor)) {
            $this->vendors[] = $vendor;
            $vendor->setTenant($this);
        }

        return $this;
    }

    public function removeVendor(VendorInterface $vendor): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->vendors->removeElement($vendor)) {
            return $this;
        }
        if ($vendor->getTenant() !== $this) {
            return $this;
        }
        $vendor->setTenant(null);

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setTenant($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->projects->removeElement($project)) {
            return $this;
        }
        if ($project->getTenant() !== $this) {
            return $this;
        }
        $project->setTenant(null);

        return $this;
    }

    /**
     * @return Collection|DocumentGroup[]
     */
    public function getDocumentGroups(): Collection
    {
        return $this->documentGroups;
    }

    public function addDocumentGroup(DocumentGroup $documentGroup): self
    {
        if (!$this->documentGroups->contains($documentGroup)) {
            $this->documentGroups[] = $documentGroup;
            $documentGroup->setTenant($this);
        }

        return $this;
    }

    public function removeDocumentGroup(DocumentGroup $documentGroup): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->documentGroups->removeElement($documentGroup)) {
            return $this;
        }
        if ($documentGroup->getTenant() !== $this) {
            return $this;
        }
        $documentGroup->setTenant(null);

        return $this;
    }

    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function addTemplate(Template $template): self
    {
        if (!$this->templates->contains($template)) {
            $this->templates[] = $template;
            $template->setTenant($this);
        }

        return $this;
    }

    public function removeTemplate(Template $template): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->templates->removeElement($template)) {
            return $this;
        }
        if ($template->getTenant() !== $this) {
            return $this;
        }
        $template->setTenant(null);

        return $this;
    }

    public function getArchives(): Collection
    {
        return $this->archives;
    }

    public function addArchive(Archive $archive): self
    {
        if (!$this->archives->contains($archive)) {
            $this->archives[] = $archive;
            $archive->setTenant($this);
        }

        return $this;
    }

    public function removeArchive(Archive $archive): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->archives->removeElement($archive)) {
            return $this;
        }
        if ($archive->getTenant() !== $this) {
            return $this;
        }
        $archive->setTenant(null);

        return $this;
    }

    public function getRootAsset(): Asset
    {
        return $this->rootAsset;
    }

    /**
     * @return Collection|AssetInterface[]
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!$this->assets->contains($asset)) {
            $this->assets[] = $asset;
            $asset->setTenant($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): self
    {
        if($asset->isRoot()) {
            return $this;
        }
        // set the owning side to null (unless already changed)
        if (!$this->assets->removeElement($asset)) {
            return $this;
        }
        if ($asset->getTenant() !== $this) {
            return $this;
        }
        $asset->setTenant(null);

        return $this;
    }

    public function getResourceAclPermissionSetPrototype(): AclPermissionSet
    {
        return $this->resourceAclPermissionSetPrototype;
    }

    public function getDocumentAclPermissionSetPrototype(): AclPermissionSet
    {
        return $this->documentAclPermissionSetPrototype;
    }

    /**
     * @return Collection|SupportedMediaType[]
     */
    public function getSupportedMediaTypes(): Collection
    {
        return $this->supportedMediaTypes;
    }

    public function addSupportedMediaType(SupportedMediaType $supportedMediaType): self
    {
        if (!$this->supportedMediaTypes->contains($supportedMediaType)) {
            $this->supportedMediaTypes[] = $supportedMediaType;
            $supportedMediaType->setTenant($this);
        }

        return $this;
    }

    public function removeSupportedMediaType(SupportedMediaType $supportedMediaType): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->supportedMediaTypes->removeElement($supportedMediaType)) {
            return $this;
        }
        if ($supportedMediaType->getTenant() !== $this) {
            return $this;
        }
        $supportedMediaType->setTenant(null);

        return $this;
    }

    // Determines whether the specific tenant supports the given media type.
    // Not used and uses repository and DQL instead
    public function supportsMediaType(MediaType $mediaType): bool
    {
        foreach ($this->supportedMediaTypes as $supportedMediumType) {
            if ($supportedMediumType->getMediaType() === $mediaType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection|CustomSpecification[]
     */
    public function getCustomSpecifications(): Collection
    {
        return $this->customSpecifications;
    }

    public function addCustomSpecification(CustomSpecification $customSpecification): self
    {
        if (!$this->customSpecifications->contains($customSpecification)) {
            $this->customSpecifications[] = $customSpecification;
            $customSpecification->setTenant($this);
        }

        return $this;
    }

    public function removeCustomSpecification(CustomSpecification $customSpecification): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->customSpecifications->removeElement($customSpecification)) {
            return $this;
        }
        if ($customSpecification->getTenant() !== $this) {
            return $this;
        }
        $customSpecification->setTenant(null);

        return $this;
    }

    /**
     * @return Collection|OverrideSetting[]
     */
    public function getOverrideSettings(): Collection
    {
        return $this->overrideSettings;
    }

    public function addOverrideSetting(OverrideSetting $overrideSetting): self
    {
        if (!$this->overrideSettings->contains($overrideSetting)) {
            $this->overrideSettings[] = $overrideSetting;
            $overrideSetting->setOrganization($this);
        }

        return $this;
    }

    public function removeOverrideSetting(OverrideSetting $overrideSetting): self
    {
        // set the owning side to null (unless already changed)
        if (!$this->overrideSettings->removeElement($overrideSetting)) {
            return $this;
        }
        if ($overrideSetting->getTenant() !== $this) {
            return $this;
        }
        $overrideSetting->setTenant(null);

        return $this;
    }

    public function getType(): OrganizationType
    {
        return OrganizationType::Tenant;
    }
}
