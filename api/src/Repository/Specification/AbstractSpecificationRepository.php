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

use App\Entity\Specification\AbstractSpecification;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AbstractSpecification|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractSpecification|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractSpecification[]    findAll()
 * @method AbstractSpecification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractSpecificationRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? AbstractSpecification::class);
    }

}
