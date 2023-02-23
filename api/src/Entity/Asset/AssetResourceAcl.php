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

namespace App\Entity\Asset;

use App\Entity\Acl\AbstractResourceAcl;
use App\Entity\Acl\HasResourceAclInterface;
// use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
// use App\Provider\ResourceAclProvider;
use App\Repository\Asset\AssetResourceAclRepository;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
// use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    uriTemplate: '/assets/{id}/resource_acl',
    uriVariables: ['id' => new Link(fromClass: Asset::class, fromProperty: 'resourceAcl')],
    operations: [
        new Get(security: "is_granted('ACL_READ_ACL', object)"),
        new Put(security: "is_granted('ACL_WRITE_ACL', object)"),
    ],
    openapiContext: ['summary' => 'Give a user special access to a resource', 'description' => 'Permissions are limited to "read" and "update" with values "ALL" or "NONE".'],
    // provider: ResourceAclProvider::class,
    //status: 200,
    // Why doesn't the following work?
    //denormalizationContext: ['groups' => ['acl:write'], AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [AclPermissionSet::class => ['tenantUserPermission'=>[], 'tenantMemberPermission'=>[], 'vendorUserPermission'=>[], 'vendorMemberPermission'=>[]]]],
    denormalizationContext: ['groups' => ['acl:write']],
    normalizationContext: ['groups' => ['acl:read']],
)]

#[ORM\Entity(repositoryClass: AssetResourceAclRepository::class)]
class AssetResourceAcl extends AbstractResourceAcl
{
    #[ApiProperty(identifier: true)] // Should I use $acl or $resource as identifier?
    #[Groups(['acl:read'])]
    #[ORM\OneToOne(inversedBy: 'resourceAcl', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[SerializedName('asset')]
    protected ?Asset $resource = null;
}
