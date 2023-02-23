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
use ApiPlatform\Metadata\Post;

use App\Processor\HttpMediaProcessor;
use ApiPlatform\Metadata\NotExposed;
use App\Entity\Interfaces\HasCollectionInterface;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Trait\UserAction\UserCreateActionTrait;
use App\Entity\User\UserInterface;
use App\Repository\Document\MediaRepository;
use App\Controller\DownloadController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            processor: HttpMediaProcessor::class,
            //deserialize: false,
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapiContext: [
                'summary' => 'Upload a Media Resource',
                'description' => 'Upload a media resource'
            ]
        ),
        new Get(
            //uriTemplate: '/medias/{id}',
            controller: DownloadController::class,
            openapiContext: ['summary' => 'Download Document Resource', 'description' => 'Download a Document resource'],
            security: "is_granted('MEDIA_READ', object)",
        ),
        //new NotExposed,
    ],
    types: ['http://schema.org/Document'],
    //security: "is_granted('ROLE_USER')",
    denormalizationContext: ['groups' => ['media_object:write']],
    normalizationContext: ['groups' => ['media_object:read', 'identifier:read', 'user_action:read']]
)]

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media implements HasUlidInterface, BelongsToTenantInterface, UploadableFileInterface, DownloadableFileInterface, HasCollectionInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;
    use UserCreateActionTrait;
    
    // #[Assert\NotNull(groups: ['media_object_create'])]
    #[Groups(['media_object:write'])]
    private ?File $file = null;
    
    #[ORM\ManyToOne(targetEntity: PhysicalMedia::class, inversedBy: 'mediaSubscribers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PhysicalMedia $physicalMedia = null;
    
    #[ORM\ManyToOne(targetEntity: MediaType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['media_object:read'])]
    private ?MediaType $mediaType = null;
    
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['media_object:read'])]
    private string $filename;

    #[Groups(['media_object:read'])]
    public function getSize(): int
    {
        return $this->physicalMedia->getSize();
    }

    public function getPhysicalMedia(): PhysicalMedia
    {
        return $this->physicalMedia;
    }

    public function hasPhysicalMedia(): bool
    {
        return (bool) $this->physicalMedia;
    }

    public function setPhysicalMedia(?PhysicalMedia $physicalMedia): self
    {
        $this->physicalMedia = $physicalMedia;

        return $this;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(?MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;

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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'class'=>get_class($this), 'filename'=>$this->filename, ];// => causes endless loop?: 'mediaType'=>$this->mediaType 'size'=>$this->size, ]);
    }
}
