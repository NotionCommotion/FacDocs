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

use App\Entity\Document\PhysicalMedia;
use App\Entity\Organization\TenantInterface;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method PhysicalMedia|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhysicalMedia|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhysicalMedia[]    findAll()
 * @method PhysicalMedia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class PhysicalMediaRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, PhysicalMedia::class);
    }

    public function getDuplicatedMediaFile(TenantInterface $tenant, Ulid $ulid): ?PhysicalMedia
    {
        return $this->createQueryBuilder('pm')
        ->join('pm.mediaSubscribers', 'm')
        ->join('m.tenant', 't')
        ->groupBy('pm.id')
        ->having('count(m.id) > 1')
        ->where('m.tenant = :tenant')
        ->andWhere('pm.id = :physicalMedia')
        ->setParameter('tenant', $tenant->getId(), 'ulid')
        ->setParameter('physicalMedia', $ulid, 'ulid')
        //->setParameters(['tenant' => $tenant->getId()->toRfc4122(), 'physicalMedia' => $physicalMediaId->toRfc4122()])
        ->getQuery()
        ->getOneOrNullResult();
    }

    public function getDuplicatedMediaFiles(TenantInterface $tenant): array
    {
        return $this->createQueryBuilder('pm')
        ->join('pm.mediaSubscribers', 'm')
        ->join('m.tenant', 't')
        ->groupBy('pm.id')
        ->having('count(m.id) > 1')
        ->where('t.id = :tenant')
        ->setParameter('tenant', $tenant->getId(), 'ulid')
        ->getQuery()
        ->getResult();
    }

    public function getOrphanPhysicalMedia(int $staleSecondsDuration = 0):array
    {
        return $this->getOrphanPhysicalMediaQueryBuilder($staleSecondsDuration)->getQuery()->getResult();
    }

    public function countOrphanPhysicalMedia(): int
    {
        return $this->getOrphanPhysicalMediaQueryBuilder($staleSecondsDuration)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    public function deleteOrphanPhysicalMedia(): int
    {
        return $this->getOrphanPhysicalMediaQueryBuilder($staleSecondsDuration)->delete()->getQuery()->execute();
    }

    private function getOrphanPhysicalMediaQueryBuilder(int $staleSecondsDuration): QueryBuilder
    {
        $query = $this->createQueryBuilder('m')
        ->leftJoin('m.documents', 'd')
        ->andWhere('d.id IS NULL');
        if($staleSecondsDuration) {
            $query->andWhere(sprintf('to_timestamp(createOn) < NOW() - INTERVAL \'%d seconds\'', $staleSecondsDuration));
        }
        return $query;
    }
}
