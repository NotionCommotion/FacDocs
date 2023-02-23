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

use App\Entity\Acl\AbstractDocumentAcl;
use App\Entity\Acl\HasDocumentAclInterface;
// use App\Provider\DocumentAclProvider;
use App\Repository\Project\ProjectDocumentAclRepository;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    uriTemplate: '/projects/{id}/document_acl',
    uriVariables: ['id' => new Link(fromClass: Project::class, fromProperty: 'documentAcl')],
    operations: [new Get(),new Put(),],
    openapiContext: ['summary' => 'Give a user special access to a document', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    // provider: DocumentAclProvider::class,
    //status: 200,
    security: "is_granted('ACL_MANAGE_PROJECT', object)",
    denormalizationContext: ['groups' => ['acl:write']],
    normalizationContext: ['groups' => ['acl:read']],
)]
#[ORM\Entity(repositoryClass: ProjectDocumentAclRepository::class)]
class ProjectDocumentAcl extends AbstractDocumentAcl
{
    #[ApiProperty(identifier: true)] // Should I use $acl or $resource as identifier?
    #[Groups(['acl:read'])]
    #[ORM\OneToOne(inversedBy: 'documentAcl', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Project $resource = null;
}
