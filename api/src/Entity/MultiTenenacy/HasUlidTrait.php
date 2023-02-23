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

namespace App\Entity\MultiTenenacy;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use App\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

trait HasUlidTrait
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    //#[ORM\CustomIdGenerator(class: 'doctrine.ulid_generator')]
    #[Groups(['identifier:read'])]
    protected ?Ulid $id = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getUlid(): Ulid
    {
        return $this->id;
    }

    public function toRfc4122(): string
    {
        return $this->id->toRfc4122();
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'class'=>get_class($this)];
    }

    // See https://github.com/api-platform/core/issues/5017
    public function __toString():string
    {
        return $this->id->toRfc4122();
    }
    
    public function __clone()
    {
        $this->id=null;
    }
}
