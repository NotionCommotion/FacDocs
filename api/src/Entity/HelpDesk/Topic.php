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

namespace App\Entity\HelpDesk;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Entity\User\UserInterface;
use App\Repository\HelpDesk\TopicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    denormalizationContext: [
        'groups' => ['help-topic:write']
    ],
    normalizationContext: [
        'groups' => ['help-topic:read', 'identifier:read'],
    ],
    shortName: 'HelpDeskTopic'
)]
#[ORM\Entity(repositoryClass: TopicRepository::class)]
#[ORM\Table(name: 'help_desk_topic')]
class Topic implements HasUlidInterface, BelongsToTenantInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;
    use UserActionTrait;

    #[Groups(['help-topic:read', 'help-topic:write'])]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $subject = null;

    // Override parent
    #[Groups(['help-topic:read'])]
    #[ORM\ManyToOne(targetEntity: UserInterface::class, inversedBy: 'helpDeskTopics', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    protected ?UserInterface $createBy = null;

    /**
     * @var Collection|Post[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'topic', targetEntity: Post::class)]
    #[Groups(['help-topic:read', 'help-topic:write'])]
    private Collection $posts;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[Groups(['help-topic:read', 'help-topic:write'])]
    private ?Status $status = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setTopic($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if (!$this->posts->removeElement($post)) {
            return $this;
        }
        if ($post->getTopic() !== $this) {
            return $this;
        }
        $post->setTopic(null);

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'subject'=>$this->subject, 'class'=>get_class($this)];
    }
}
