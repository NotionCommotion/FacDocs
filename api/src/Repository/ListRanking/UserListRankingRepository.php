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

namespace App\Repository\ListRanking;

use App\Entity\ListRanking\UserListRanking;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserListRanking|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserListRanking|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserListRanking[]    findAll()
 * @method UserListRanking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class UserListRankingRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, UserListRanking::class);
    }
}
