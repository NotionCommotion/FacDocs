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

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

// Add to class: #[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]

trait SoftDeleteTrait
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user_action:read'])]
    protected $deleteAt;

    /**
     * Set or clear the delete at timestamp.
     */
    public function setDeleteAt(DateTime $deleteAt = null):self
    {
        $this->deleteAt = $deleteAt;

        return $this;
    }

    /**
     * Get the delete at timestamp value. Will return null if
     * the entity has not been soft delete.
     */
    public function getDeleteAt():?DateTime
    {
        return $this->deleteAt;
    }

    public function isDeleted():bool
    {
        return null !== $this->deleteAt;
    }
}
