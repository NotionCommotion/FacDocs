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

namespace App\Entity\UserManual;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Trait\NonIdentifyingIdTrait;
use App\Repository\UserManual\UserManualRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(),
        new Put(security: "is_granted('ROLE_MANAGE_USER_MANUAL')"),
        new Patch(security: "is_granted('ROLE_MANAGE_USER_MANUAL')"),
        new Delete(security: "is_granted('ROLE_MANAGE_USER_MANUAL')"),
        new GetCollection(),
        new Post(security: "is_granted('ROLE_MANAGE_USER_MANUAL')")
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Entity(repositoryClass: UserManualRepository::class, readOnly: true)]
class UserManual
{
    use NonIdentifyingIdTrait;
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    #[ApiProperty(identifier: true, openapiContext: ['example' => 'somename'], readableLink: false, writableLink: false)]
    private ?string $topic = null;
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;
    #[ORM\Column(type: 'integer')]
    private ?int $list_order = null;
    #[ORM\Column(type: 'boolean')]
    private ?bool $display_list = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $keywords = null;
    /**
     * @var $this|self|null
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ApiProperty(openapiContext: ['example' => 'user_manuals/root'], readableLink: false, writableLink: false)]
    private ?self $parent = null;
    /**
     * @var Collection|self[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['topic'=>$this->topic, 'class'=>get_class($this)];
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getListOrder(): ?int
    {
        return $this->list_order;
    }

    public function setListOrder(int $list_order): self
    {
        $this->list_order = $list_order;

        return $this;
    }

    public function getDisplayList(): ?bool
    {
        return $this->display_list;
    }

    public function setDisplayList(bool $display_list): self
    {
        $this->display_list = $display_list;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param \App\Entity\UserManual\UserManual|null $parent
     */
    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if (!$this->children->removeElement($child)) {
            return $this;
        }
        if ($child->getParent() !== $this) {
            return $this;
        }
        $child->setParent(null);

        return $this;
    }
}
