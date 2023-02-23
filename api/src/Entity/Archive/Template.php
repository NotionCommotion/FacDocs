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

namespace App\Entity\Archive;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasResourceAclTrait;
// use App\Entity\Acl\AclDefaultRole;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\Archive\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_TEMPLATE', read: 'ROLE_READ_TEMPLATE', update: 'ROLE_UPDATE_TEMPLATE', delete: 'ROLE_MANAGE_TEMPLATE', manageAcl: 'ROLE_MANAGE_ACL_TEMPLATE')]
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
            denormalizationContext: ['groups' => ['template:write']],
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
        )
    ],
    denormalizationContext: ['groups' => ['template:write']],
    normalizationContext: ['groups' => ['template:read', 'user_action:read', 'identifier:read', 'public_id:read']]
)]
#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'templates')])]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'name'])]
class Template implements HasUlidInterface, BelongsToTenantInterface, HasResourceAclInterface
{
    use HasUlidTrait, HasResourceAclTrait {
        HasResourceAclTrait::debug insteadof HasUlidTrait;
    }
    use BelongsToTenantTrait;
    use UserActionTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?TemplateResourceAcl $resourceAcl = null;

    #[Groups(['template:read', 'template:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    private ?string $name = null;
    #[Groups(['template:read', 'template:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    #[Groups(['template:read', 'template:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $html = null;
    #[Groups(['template:read'])]
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Archive::class)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private $archives;

    public function __construct()
    {
        $this->archives = new ArrayCollection();
    }

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new TemplateResourceAcl($entity);
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

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;

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
            $archive->setTemplate($this);
        }

        return $this;
    }

    public function removeArchive(Archive $archive): self
    {
        // set the owning side to null (unless already changed)
        if ($this->archives->removeElement($archive) && $archive->getTemplate() === $this) {
            $archive->setTemplate(null);
        }

        return $this;
    }
}
