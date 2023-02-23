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

use App\Entity\ListRanking\AbstractRankedList;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AbstractRankedList|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractRankedList|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractRankedList[]    findAll()
 * @method AbstractRankedList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractRankedListRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AbstractRankedList::class);
    }
}
