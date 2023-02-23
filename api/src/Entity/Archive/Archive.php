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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Project\Project;
use App\Entity\Trait\UserAction\UserCreateActionTrait;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasResourceAclTrait;
// use App\Entity\Acl\AclDefaultRole;
use App\Processor\ArchiveCreaterDecoratedProcessor;
use App\Processor\ArchiveDeleterDecoratedProcessor;
use App\Repository\Archive\ArchiveRepository;
use App\Controller\DownloadArchiveController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

//#[AclDefaultRole(create: 'ROLE_MANAGE_ARCHIVE', read: 'ROLE_READ_ARCHIVE', update: 'ROLE_UPDATE_ARCHIVE', delete: 'ROLE_MANAGE_ARCHIVE', manageAcl: 'ROLE_MANAGE_ACL_ARCHIVE')]
#[ApiResource(
    operations: [
        new GetCollection(),    // Security handled by ResourceAclExtension
        new Get(
            security: "is_granted('ACL_RESOURCE_READ', object)",
        ),
        new Delete(
            processor: ArchiveDeleterDecoratedProcessor::class,
            security: "is_granted('ACL_RESOURCE_DELETE', object)",
            //deserialize: false,
        ),
        new Post(
            processor: ArchiveCreaterDecoratedProcessor::class,
            //Consider changing from securityPostDenormalize to just security.
            securityPostDenormalize: "is_granted('ACL_RESOURCE_CREATE', object)",
            //deserialize: false,
        ),
        new Get(
            uriTemplate: '/archives/{id}/download',
            controller: DownloadArchiveController::class,
            security: "is_granted('ACL_RESOURCE_DOWNLOAD', object)",
            openapiContext: ['summary' => 'Download Archive Resource', 'description' => 'Download an archive resource']
        ),
    ],
    security: "is_granted('ROLE_MANAGE_ARCHIVE')",
    denormalizationContext: ['groups' => ['archive:write']],
    normalizationContext: ['groups' => ['archive:read']]
)]
#[ORM\Entity(repositoryClass: ArchiveRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'archives')])]
class Archive implements HasUlidInterface, BelongsToTenantInterface, HasResourceAclInterface
{
    use HasUlidTrait, HasResourceAclTrait {
        HasResourceAclTrait::debug insteadof HasUlidTrait;
    }
    use BelongsToTenantTrait;
    use UserCreateActionTrait;

    #[ORM\OneToOne(mappedBy: 'resource', cascade: ['persist', 'remove'])]
    #[Groups(['acl_admin:read'])]
    protected ?ArchiveResourceAcl $resourceAcl = null;

    #[Groups(['archive:read', 'archive:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    private ?string $name = null;

    #[Groups(['archive:read', 'archive:write'])]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'archives')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'projects/00000000000000000000000000'])]
    private ?Project $project = null;

    #[Groups(['archive:read', 'archive:write'])]
    #[ORM\ManyToOne(targetEntity: Template::class, inversedBy: 'archives')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'templates/00000000000000000000000000'])]
    private $template;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $filename = null;

    #[Groups(['archive:read', 'archive:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    static public function createResourceAcl(HasResourceAclInterface $entity): ResourceAclInterface
    {
        return new ArchiveResourceAcl($entity);
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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

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

    #[Groups(['archive:read'])]
    #[ApiProperty(types: ['http://schema.org/contentUrl'])]
    public function getPath(): string
    {
        // Fix
        return sprintf('/archive/%s/download', $this->getId());
    }
}
