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

use App\Entity\Document\Document;
use App\Entity\Document\Media;
use App\Entity\User\BasicUserInterface;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class MediaRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry, private Security $security)
    {
        parent::__construct($managerRegistry, Media::class);
    }

    public function getMediaUsedByDocument(Ulid $ulid):array
    {
        return $this->createQueryBuilder('m')
        ->join('m.documents', 'd')
        ->andWhere('d.id = :id')
        ->setParameter('id', $ulid, 'ulid')
        ->getQuery()
        ->getResult();
    }

    public function isOrphan($media):bool
    {
        return count($this->createQueryBuilder('void')
        ->select('d')
        ->from(Document::class, 'd')
        ->join('d.media', 'm')
        ->andWhere('m.id = :id')
        ->setParameter('id', $media->getId(), 'ulid')
        ->getQuery()
        ->getResult())?false:true;
    }

    public function getOrphanMedia(int $staleSecondsDuration = 0):array
    {
        return $this->getOrphanMediaQueryBuilder($staleSecondsDuration)->getQuery()->getResult();
    }

    public function countOrphanMedia(): int
    {
        return $this->getOrphanMediaQueryBuilder($staleSecondsDuration)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    public function deleteOrphanMedia(): int
    {
        return $this->getOrphanMediaQueryBuilder($staleSecondsDuration)->delete()->getQuery()->execute();
    }

    private function getOrphanMediaQueryBuilder(int $staleSecondsDuration): QueryBuilder
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
