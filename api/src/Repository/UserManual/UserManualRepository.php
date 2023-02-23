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

namespace App\Repository\UserManual;

use App\Entity\UserManual\UserManual;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserManual|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserManual|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserManual[]    findAll()
 * @method UserManual[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class UserManualRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, UserManual::class);
    }
}
