<?php

// Not sure if this will ever be used.  Having issues with persisting some entities with Enum and doctrine custom mapping.

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait ChangeDetectionTrait
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $changeDetector=0;

    protected function incrementChangeDetector(): self
    {
        $this->changeDetector = 1 + $this->changeDetector??0;

        return $this;
    }
}
