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

use App\Repository\Acl\ResourceAclRepository;
use App\Entity\Acl\HasResourceAclInterface;

use App\Entity\Project\ProjectResourceAcl;
use App\Entity\Asset\AssetResourceAcl;
use App\Entity\DocumentGroup\DocumentGroupResourceAcl;
use App\Entity\User\TenantUserResourceAcl;
use App\Entity\Organization\VendorResourceAcl;
use App\Entity\User\VendorUserResourceAcl;
use App\Entity\Specification\CustomSpecificationResourceAcl;
use App\Entity\Archive\TemplateResourceAcl;
use App\Entity\Archive\ArchiveResourceAcl;

use App\Exception\InvalidAclPermissionException;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: ResourceAclRepository::class)]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap(value: [
    'project' => ProjectResourceAcl::class,
    'asset' => AssetResourceAcl::class,
    'doc_group' => DocumentGroupResourceAcl::class,
    'tenant_user' => TenantUserResourceAcl::class,
    'vendor' => VendorResourceAcl::class,
    'vendor_user' => VendorUserResourceAcl::class,
    'cust_spec' => CustomSpecificationResourceAcl::class,
    'template' => TemplateResourceAcl::class,
    'archive' => ArchiveResourceAcl::class,
])]
#[ORM\Table(name: 'resource_acl')]
// Not sure whether NotExposed is required.
#[NotExposed]
abstract class AbstractResourceAcl extends AbstractAcl implements ResourceAclInterface
{
    private const VALID_PERMISSIONS = ['ALL', 'NONE'];
    
    #[ORM\OneToMany(mappedBy: 'acl', targetEntity: ResourceAclMember::class, orphanRemoval: true)]
    #[Groups(['acl:read', 'acl:write'])]
    protected Collection $members;

    #[Groups(['acl:read', 'acl:write'])]
    #[ORM\Embedded(columnPrefix: 'resource_')]
    protected AclPermissionSet $permissionSet;

    public function __construct(HasResourceAclInterface $entity){
        $this->permissionSet = clone $entity->getTenant()->getResourceAclPermissionSetPrototype();
        parent::__construct($entity);
    }

    // Ignore settings for create and delete and leave as NULL.
    public static function normalize(AclPermission $permission):array
    {
        return [
            'read' => $permission->getRead()->name,
            'update' => $permission->getUpdate()->name,
        ];
    }

    public static function denormalize(array $permission):AclPermission
    {
        $permission = AclPermission::create($permission['read']??null, $permission['update']??null);
        if(!in_array($permission->getRead()->name, self::VALID_PERMISSIONS)) {
            throw new InvalidAclPermissionException($permission->getRead()->name.' is not a valid permission value');
        }
        if(!in_array($permission->getUpdate()->name, self::VALID_PERMISSIONS)) {
            throw new InvalidAclPermissionException($permission->getUpdate()->name.' is not a valid permission value');
        }
        return $permission;
    }
}
