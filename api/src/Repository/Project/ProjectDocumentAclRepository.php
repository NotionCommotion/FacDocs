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

namespace App\Repository\Project;

use App\Entity\Project\ProjectDocumentAcl;
use App\Repository\Acl\DocumentAclRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ProjectDocumentAclRepository extends DocumentAclRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProjectDocumentAcl::class);
    }
}
