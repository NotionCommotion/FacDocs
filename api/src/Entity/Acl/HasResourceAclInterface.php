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
use Symfony\Component\Uid\Ulid;

interface HasResourceAclInterface extends HasAclInterface
{
    public function getResourceAcl(): ?ResourceAclInterface;
    public function setResourceAcl(ResourceAclInterface $resourceAcl): self;
    //public function getAclResourceHash(): string;
    static public function createResourceAcl(self $entity): ResourceAclInterface;
    public function setId(Ulid $id): self;
}
