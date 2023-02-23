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

use App\Repository\Acl\DocumentAclRepository;
use App\Entity\Acl\HasDocumentAclInterface;

use App\Entity\Project\ProjectDocumentAcl;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: DocumentAclRepository::class)]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap(value: [
    'project' => ProjectDocumentAcl::class,
])]
#[ORM\Table(name: 'document_acl')]
// Not sure whether NotExposed is required.
#[NotExposed]
abstract class AbstractDocumentAcl extends AbstractAcl implements DocumentAclInterface
{
    #[ORM\OneToMany(mappedBy: 'acl', targetEntity: DocumentAclMember::class, orphanRemoval: true)]
    #[Groups(['acl:read', 'acl:write'])]
    protected Collection $members;

    #[Groups(['acl:read', 'acl:write'])]
    #[ORM\Embedded(columnPrefix: 'document_')]
    protected AclPermissionSet $permissionSet;

    public function __construct(HasDocumentAclInterface $entity)
    {
        $this->permissionSet = clone $entity->getTenant()->getDocumentAclPermissionSetPrototype();
        parent::__construct($entity);
    }

    public static function normalize(AclPermission $permission):array
    {
        return [
            'read' => $permission->getRead()->name,
            'update' => $permission->getUpdate()->name,
            'create' => $permission->getCreate()->name,
            'delete' => $permission->getDelete()->name,
        ];
    }

    public static function denormalize(array $permission):AclPermission
    {
        // All validation performed by AclPermission.
        return AclPermission::create($permission['read']??null, $permission['update']??null, $permission['create']??null, $permission['delete']??null); 
    }
}
