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
use ApiPlatform\Metadata\ApiProperty;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\HelpDesk\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    denormalizationContext: [
        'groups' => ['help-post:write']
    ],
    normalizationContext: [
        'groups' => ['help-post:read', 'identifier:read'],
    ],
    shortName: 'HelpDeskPost'
)]
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'help_desk_post')]
class Post implements HasUlidInterface, BelongsToTenantInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;
    use UserActionTrait;

    #[Groups(['help-post:read', 'help-post:write'])]
    #[ORM\ManyToOne(targetEntity: Topic::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'help_desk_topics/00000000000000000000000000'])]
    private ?Topic $topic = null;

    #[Groups(['help-post:read', 'help-post:write'])]
    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    public function setTopic(?Topic $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
