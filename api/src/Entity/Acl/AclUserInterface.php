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

interface AclUserInterface extends AccessControlAwareInterface
{
    // Used to get correct permissions based on user type.
    public function getAclUserPermission(AclPermissionSet $permissionSet): AclPermission;
    public function getAclMemberPermission(AclPermissionSet $permissionSet): AclPermission;

}