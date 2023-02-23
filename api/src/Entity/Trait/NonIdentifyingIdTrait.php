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

namespace App\Entity\Trait;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

trait NonIdentifyingIdTrait
{
    #[ORM\Id]
    #[ApiProperty(identifier: false)]
    #[ORM\GeneratedValue]
    // #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Ignore]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
