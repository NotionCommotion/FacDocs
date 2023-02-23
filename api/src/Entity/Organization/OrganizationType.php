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

namespace App\Entity\Organization;

enum OrganizationType
{
    case Tenant;
    case Vendor;
    case System;

    public static function fromName(string $name): self
    {
        return \constant('self::'.$name);
    }

    public function isTenant(): bool
    {
        return $this->name === 'Tenant';
    }

    public function isVendor(): bool
    {
        return $this->name === 'Vendor';
    }

    public function isSystem(): bool
    {
        return $this->name === 'System';
    }

    public function getUserClass(): string
    {
        return sprintf('App\Entity\User\%sUser', $this->name);
    }
}
