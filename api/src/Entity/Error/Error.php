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

namespace App\Entity\Error;

use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\User\AbstractUser;
use App\Repository\Error\ErrorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Trait\UserAction\UserCreateActionTrait;

#[ORM\Entity(repositoryClass: ErrorRepository::class)]
class Error implements HasUlidInterface
{
    use HasUlidTrait;
    use UserCreateActionTrait;

    #[ORM\ManyToOne(targetEntity: AbstractUser::class, inversedBy: 'errors')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(type: 'datetime')]
    private $errorAt;

    #[ORM\Column(type: 'text')]
    private $description;

    public function getUser(): ?AbstractUser
    {
        return $this->user;
    }

    public function setUser(?AbstractUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
