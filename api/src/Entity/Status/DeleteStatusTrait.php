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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait DeleteStatusTrait
{
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['delete_status:read', 'delete_status:write'])]
    private bool $deleteStatus = false;

    public function getDeleteStatus(): ?bool
    {
        return $this->deleteStatus;
    }

    public function setDeleteStatus(bool $deleteStatus): self
    {
        $this->deleteStatus = $deleteStatus;

        return $this;
    }
}
