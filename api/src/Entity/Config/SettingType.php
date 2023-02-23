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

namespace App\Entity\Config;

use Doctrine\ORM\Mapping as ORM;

/**
 * Exposed via service.
 */
#[ORM\Entity(readOnly: true)]
class SettingType
{
    public function __construct(
        #[ORM\Column(type: 'string', length: 16)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        private string $type,

        #[ORM\Column(type: 'text')] private string $description
    ) {
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['type'=>$this->type, 'class'=>get_class($this)];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
