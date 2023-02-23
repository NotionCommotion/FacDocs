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

use App\Entity\Document\DocumentType;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentType|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentType|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentType[]    findAll()
 * @method DocumentType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class DocumentTypeRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, DocumentType::class);
    }
}
