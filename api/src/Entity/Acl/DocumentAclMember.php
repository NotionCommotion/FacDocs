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

namespace App\Entity\Acl;

use App\Entity\Specification\AbstractSpecification;
use App\Entity\Specification\SpecificationInterface;
use App\Repository\Acl\DocumentAclMemberRepository;
use App\Entity\User\UserInterface;
use App\Entity\Project\Project;
use App\Provider\AclMemberProvider;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
//use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\NotExposed;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

// Evidently, internally needs a generic GET endpoint.
//#[NotExposed]
#[ApiResource(
    operations: [new Get(), new Put(), new Delete()],//, new Patch()],
    openapiContext: ['summary' => 'Give a user special access to a document', 'description' => 'ID is a user/acl composite ID and is primarily used to support internal requests.  Permissions are limited to "read", "update", "create", and "delete" with values "ALL", "NONE", "OWNER", "COWORKER", and "VENDOR".'],
    security: "is_granted('ROLE_MANAGE_ACL_MEMBER', object)",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ApiResource(
    uriTemplate: '/projects/{id}/users/{userId}/documentMember',
    uriVariables: ['id' => new Link(fromClass: Project::class), 'userId' => new Link(fromClass: UserInterface::class)],
    operations: [new Get(), new Put(), new Post(), new Delete()],//, new Patch()],
    provider: AclMemberProvider::class,
    openapiContext: ['summary' => 'Give a user special access to a document', 'description' => 'Permissions are limited to "read", "update", "create", and "delete" with values "ALL", "NONE", "OWNER", "COWORKER", and "VENDOR".'],
    //status: 200,
    security: "is_granted('ROLE_MANAGE_ACL_MEMBER')",
    denormalizationContext: ['groups' => ['acl_member:write']],
    normalizationContext: ['groups' => ['acl_member:read']],
)]
#[ORM\Entity(repositoryClass: DocumentAclMemberRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'acl_id'])] // Maybe not necessary?
class DocumentAclMember extends AbstractAclMember
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['acl_member:read'])]
    //#[ApiProperty(readableLink: false, writableLink: false)]
    protected AbstractDocumentAcl $acl;

    #[ORM\ManyToOne]
    #[Groups(['acl_member:read', 'acl_member:write'])]
    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'])]
    private ?AbstractSpecification  $allowedSpecification = null;

    public function __construct
    (
        HasDocumentAclInterface $resource,

        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'documentAclMembers')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        #[Groups(['acl_member:read'])]
        protected UserInterface $user,
    )
    {
        parent::__construct($resource->getDocumentAcl());
    }

    public function getAllowedSpecification(): ?SpecificationInterface 
    {
        return $this->allowedSpecification;
    }

    public function setAllowedSpecification(?SpecificationInterface  $allowedSpecification): self
    {
        $this->allowedSpecification = $allowedSpecification;

        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_merge(parent::debug($follow, $verbose, $hide), ['allowedSpecification' =>(string) $this->allowedSpecification]);
    }

    public static function normalize(AclPermission $permission):array
    {
        return AbstractDocumentAcl::normalize($permission);
    }

    public static function denormalize(array $permission):AclPermission
    {
        return AbstractDocumentAcl::denormalize($permission);
    }
}
