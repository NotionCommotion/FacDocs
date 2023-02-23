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

namespace App\Repository\Organization;

use App\Entity\Document\MediaType;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\TenantInterface;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tenant[]    findAll()
 * @method Tenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class TenantRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Tenant::class);
    }

    public function supportsMediaType(TenantInterface $tenant, MediaType $mediaType): bool
    {
        return (bool) $this->createQueryBuilder('t')
        ->join('t.supportedMediaTypes', 'smt')
        ->where('smt.mediaType = :mediaType')
        ->setParameter('mediaType', $mediaType)
        ->getQuery()
        ->getResult();
    }
}
