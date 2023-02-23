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
use ApiPlatform\Metadata\Post;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\Document\SupportedMediaTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(), new Delete(), new Post(), new GetCollection()], denormalizationContext: ['groups' => ['supported_media_type:write']], normalizationContext: ['groups' => ['supported_media_type:read', 'user_action:read']])]
#[ORM\Entity(repositoryClass: SupportedMediaTypeRepository::class)]
#[ORM\UniqueConstraint(columns: ['tenant_id', 'media_type_id'])]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'tenant', joinTable: new ORM\JoinTable(name: 'tenant'), inversedBy: 'supportedMediaTypes')])]
class SupportedMediaType implements HasUlidInterface, BelongsToTenantInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;
    use UserActionTrait;

    #[ORM\ManyToOne(targetEntity: MediaType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['supported_media_type:read', 'supported_media_type:write'])]
    #[ApiProperty(openapiContext: ['example' => 'media_types/type=text;subtype=csv'])]
    private ?MediaType $mediaType = null;

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(?MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }
}
