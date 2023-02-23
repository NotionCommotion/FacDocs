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

namespace App\Repository\Document;

use App\Entity\Document\MediaType;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MediaType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediaType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediaType[]    findAll()
 * @method MediaType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class MediaTypeRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, MediaType::class);
    }
}
