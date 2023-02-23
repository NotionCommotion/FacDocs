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

namespace App\Repository\Archive;

use App\Entity\Archive\Archive;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Archive|null find($id, $lockMode = null, $lockVersion = null)
 * @method Archive|null findOneBy(array $criteria, array $orderBy = null)
 * @method Archive[]    findAll()
 * @method Archive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class ArchiveRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Archive::class);
    }
}
