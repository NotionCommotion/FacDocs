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
class DataType
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 12)]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        private string $dataType
    ) {
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['dataType'=>$this->dataType, 'class'=>get_class($this)];
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }
}
