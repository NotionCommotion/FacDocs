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

use App\Entity\User\BasicUserInterface;
use App\Entity\Document\Document;
use Doctrine\Common\Collections\Collection;

// Since ability to change permissions associated with documents must be controlled, must have  HasResourceAcl to do so.
interface HasDocumentAclInterface extends HasResourceAclInterface
{
    public function getDocumentAcl(): ?DocumentAclInterface;
    public function setDocumentAcl(DocumentAclInterface $documentAcl): self;
    static public function createDocumentAcl(self $entity): DocumentAclInterface;
}
