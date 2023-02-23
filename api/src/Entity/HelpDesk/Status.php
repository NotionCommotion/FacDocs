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

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'help_desk_status')]
class Status
{
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        #[ORM\Column(type: 'string', length: 16)]
        private string $id
    ) {
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['status'=>$this->id, 'class'=>get_class($this)];
    }

    public function getStatus(): string
    {
        return $this->id;
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
