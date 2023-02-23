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

namespace App\Repository\Organization;

use App\Entity\Organization\SystemOrganization;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\NilUlid;

/**
 * @method SystemOrganization|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemOrganization|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemOrganization[]    findAll()
 * @method SystemOrganization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class SystemOrganizationRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SystemOrganization::class);
    }

    public function findRoot(): SystemOrganization
    {
        return $this->find(new NilUlid);;
    }
}
