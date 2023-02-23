<?php

declare(strict_types=1);

namespace App\Repository\Acl;

use App\Entity\Acl\DocumentAclMember;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DocumentAclMemberRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, DocumentAclMember::class);
    }
}