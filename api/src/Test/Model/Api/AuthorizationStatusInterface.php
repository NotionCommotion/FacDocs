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

interface AuthorizationStatusInterface 
{
    public function getUser():UserInterface;
    public function getUserRoles(): array;
    public function getResourceClass():string;
    public function getResourceId():mixed;
    public function isAuthorized(): bool;
    public function getAnticipatedStatusCode(): int;
    public function setAnticipatedStatusCode(int $anticipatedStatusCode): self;
    public function isCollection(): bool;
    public function setIsCollection(bool $isCollection): self;
    public function getMessage(): string;
    public function getData():array;
    public function getDebugMessage():string;
    public function toArray(): array;
    public function debug(): array;
}
