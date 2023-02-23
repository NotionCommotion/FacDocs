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

namespace App\Repository\Specification;

use App\Entity\Specification\CsiSpecification;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CsiSpecification|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsiSpecification|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsiSpecification[]    findAll()
 * @method CsiSpecification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class CsiSpecificationRepository extends AbstractSpecificationRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, CsiSpecification::class);
    }
}
