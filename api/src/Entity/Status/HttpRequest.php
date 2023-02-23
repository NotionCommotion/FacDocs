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

namespace App\Entity\Status;

use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\User\UserInterface;
use App\Repository\Status\HttpRequestRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HttpRequestRepository::class)]
class HttpRequest implements HasUlidInterface, BelongsToTenantInterface
{
    use HasUlidTrait;
    use BelongsToTenantTrait;

    #[ORM\Column(type: 'datetime')]
    private ?DateTime $requestAt = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserInterface $requestBy = null;

    #[ORM\Column(type: 'text')]
    private ?string $request = null;

    #[ORM\Column(type: 'text')]
    private ?string $url = null;

    public function getRequestAt(): ?DateTime
    {
        return $this->requestAt;
    }

    public function setRequestAt(DateTime $requestAt): self
    {
        $this->requestAt = $requestAt;

        return $this;
    }

    public function getRequestBy(): ?UserInterface
    {
        return $this->requestBy;
    }

    public function setRequestBy(?UserInterface $user): self
    {
        $this->requestBy = $user;

        return $this;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setRequest(string $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
