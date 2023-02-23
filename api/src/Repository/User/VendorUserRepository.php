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

use App\Entity\User\VendorUser;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VendorUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method VendorUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method VendorUser[]    findAll()
 * @method VendorUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class VendorUserRepository extends UserRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, VendorUser::class);
    }
}
