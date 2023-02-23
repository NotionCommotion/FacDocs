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

namespace App\Repository\User;

use App\Entity\User\SystemUser;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\NilUlid;

/**
 * @method SystemUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SystemUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SystemUser[]    findAll()
 * @method SystemUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class SystemUserRepository extends UserRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, SystemUser::class);
    }

    public function findRoot(): SystemUser
    {
        return $this->find(new NilUlid);;
    }
}
