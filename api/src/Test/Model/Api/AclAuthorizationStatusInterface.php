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

namespace App\Test\Model\Api;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\User\UserInterface;
use App\Entity\Acl\HasAclInterface;
use Symfony\Component\Uid\Ulid;

interface AclAuthorizationStatusInterface extends AuthorizationStatusInterface
{
    public function getAction(): string;
    //public function getInfo():array;
    public function getResource():HasAclInterface;
    public function getMemberRoles(): ?array;
    //public function getExpectedContentType():string;
    public function getResourceAclPermissionSet(): AclPermissionSet;
    public function getResourceMemberAclPermission(): ?AclPermission;
    public function toArray(bool $readUpdateOnly=true): array;
    public function debug(): array;
}
