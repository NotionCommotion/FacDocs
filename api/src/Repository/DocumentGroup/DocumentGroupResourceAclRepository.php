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

namespace App\Repository\DocumentGroup;

use App\Entity\DocumentGroup\DocumentGroupResourceAcl;
use App\Repository\Acl\ResourceAclRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DocumentGroupResourceAclRepository extends ResourceAclRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, DocumentGroupResourceAcl::class);
    }
}
