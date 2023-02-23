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

namespace App\Entity\Acl;
use Doctrine\Common\Collections\Collection;

/**
 * Just used to trigger an persist/update event to store roles which validates the json roles property.  Used with Abstract User and AbstractMember.
 */
interface HasRolesInterface extends AccessControlAwareInterface
{
    public function getRoles(): array;
    public function setRoles(array $roles): self;
    public function addRole(string $role): self;
    public function removeRole(string $role): self;
    public function setRoleConstraint(Collection $roles): self;
}
