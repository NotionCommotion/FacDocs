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

use App\Entity\Organization\TenantInterface;
use App\Entity\User\UserInterface;

interface BelongsToTenantInterface
{
    public function getTenant(): ?TenantInterface;

    public function setTenant(TenantInterface $tenant): self;

    // public static function create(TenantInterface $tenant, ?UserInterface $user = null): self;
}
