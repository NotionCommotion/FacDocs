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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Provider\DuplicatedMediaProvider;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\Groups;
/*
// Or can do this way...
#[Get(
    provider: DuplicatedMediaProvider::class,
    uriTemplate: '/media/duplicated/{id}'
)]
*/
#[ApiResource(
    //shortName: 'Media',
    provider: DuplicatedMediaProvider::class,
    operations: [
        new Get(
            uriTemplate: '/media/duplicated/{id}',
        ),
        new GetCollection(
            uriTemplate: '/media/duplicated',
        )
    ],
    normalizationContext: ['groups' => ['duplicated_media:read', 'media_object:read', 'identifier:read', 'user_action:read']]
)]
class DuplicatedMedia
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups(['duplicated_media:read'])]
        private Ulid $ulid,
        #[Groups(['duplicated_media:read'])]
        private int $size,
        #[Groups(['duplicated_media:read'])]
        private MediaType $mediaType,
        //#[ApiProperty(readableLink: false, writableLink: false)]
        #[Groups(['duplicated_media:read'])]
        private ArrayCollection $arrayCollection
    )
    {
    }

    #[ApiProperty]
    #[Groups(['duplicated_media:read'])]
    public function getUlid()
    {
        return $this->ulid->toRfc4122();
    }

    public function getId()
    {
        return $this->ulid;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function getMediaSubscribers(): ArrayCollection
    {
        return $this->arrayCollection;
    }
}
