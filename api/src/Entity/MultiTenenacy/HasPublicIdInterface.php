<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
//Currently, use ULID so these are not needed. Add as secondary identifier if URLs get too long when making GET search requests (maybe use for assets, projects, etc)


declare(strict_types=1);

namespace App\Entity\MultiTenenacy;

interface HasPublicIdInterface extends BelongsToTenantInterface
{
    public function getPublicId(): ?int;

    public function setPublicId(int $publicId): self;

    public function getPublicIdIndex(): ?string;
}
