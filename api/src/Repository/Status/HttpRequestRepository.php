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

namespace App\Repository\Status;

use App\Entity\Status\HttpRequest;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HttpRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method HttpRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method HttpRequest[]    findAll()
 * @method HttpRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class HttpRequestRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, HttpRequest::class);
    }
}
