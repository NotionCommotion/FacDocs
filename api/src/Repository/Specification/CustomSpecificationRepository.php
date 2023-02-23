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

use App\Entity\Specification\CustomSpecification;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CustomSpecification|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomSpecification|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomSpecification[]    findAll()
 * @method CustomSpecification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class CustomSpecificationRepository extends AbstractSpecificationRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, CustomSpecification::class);
    }

    public function validate(CustomSpecification $customSpecification): void
    {
        // TODO - If recursive, throw exception
    }
}