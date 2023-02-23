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

use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Repository\Document\PhysicalMediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhysicalMediaRepository::class)]
#[ORM\Table]
#[ORM\Index(name: 'idx_physical_file_hash', columns: ['hash'])]
class PhysicalMedia implements HasUlidInterface
{
    use HasUlidTrait;

    #[ORM\Column(type: 'integer')]
    private ?int $size = null;

    #[ORM\ManyToOne(targetEntity: MediaType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?MediaType $mediaType = null;

    #[ORM\Column(type: 'string', length: 32)]
    private ?string $hash = null;

    /**
     * @var Media[]|Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'physicalMedia', orphanRemoval: true)]
    private Collection $mediaSubscribers;

    public function __construct()
    {
        $this->mediaSubscribers = new ArrayCollection();
    }

    public function getPathname(): string
    {
        return sprintf('%s/%s', $this->getPath(), $this->getFilename());
    }

    public function getFilename(): string
    {
        return sprintf('%s.%s', $this->id->toRfc4122(), $this->mediaType->getDefaultExtension() ?? 'unknown');
    }

    public function getPath(): string
    {
        $dateTimeImmutable = $this->getId()->getDateTime();
        return sprintf('%s/%s/%s', $dateTimeImmutable->format('Y'), $dateTimeImmutable->format('m'), $dateTimeImmutable->format('d'));
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMediaType(): ?MediaType
    {
        return $this->mediaType;
    }

    public function setMediaType(MediaType $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return Collection|Media[]
     */
    public function getMediaSubscribers(): Collection
    {
        return $this->mediaSubscribers;
    }

    public function addMediaSubscriber(Media $media): self
    {
        if (!$this->mediaSubscribers->contains($media)) {
            $this->mediaSubscribers[] = $media;
            $media->setPhysicalMedia($this);
        }

        return $this;
    }

    public function removeMediaSubscriber(Media $media): self
    {
        if (!$this->mediaSubscribers->removeElement($media)) {
            return $this;
        }

        if ($media->getPhysicalMedia() !== $this) {
            return $this;
        }

        $media->setPhysicalMedia(null);

        return $this;
    }
}
