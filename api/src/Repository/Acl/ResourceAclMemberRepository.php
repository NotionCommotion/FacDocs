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

namespace App\Repository\Acl;

use App\Entity\Acl\ResourceAclMember;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceAclMemberRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? ResourceAclMember::class);
    }
}
