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
use App\Entity\Project\Project;
use App\Entity\Document\Media;
use App\Entity\Acl\AbstractDocumentAcl;
use App\Entity\User\BasicUserInterface;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class DocumentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Document::class);
    }

    // Sorts them by the most used filename.  Used by ArchiveBuilder
    public function getProjectDocuments(Project $project):array
    {
        return $this->createQueryBuilder('d')
        ->addSelect('COUNT(m.filename) AS HIDDEN filename_count')
        ->join('d.media', 'm')
        ->join('m.physicalMedia', 'pm')
        ->where('d.project = :project')
        ->groupBy('pm.id')
        ->addGroupBy('d')
        ->orderBy('filename_count', 'DESC')
        ->setParameter('project', $project)
        ->getQuery()
        ->getResult();
    }

    public function getDocumentsWhichUseMedia(Ulid $ulid):array
    {
        return $this->createQueryBuilder('d')
        ->join('d.medias', 'm')
        ->andWhere('m.id = :id')
        ->setParameter('id', $ulid, 'ulid')
        ->getQuery()
        ->getResult();
    }

    public function userHasAccessToMedia(BasicUserInterface $user, Media $media, string $requiredRole):bool
    {
        $qb = $this->createQueryBuilder('d')
        ->join('d.media', 'm')
        ->andWhere('m.id = :id')
        ->setParameter('id', $media->getId(), 'ulid');

        $this->getEntityManager()->getRepository(AbstractDocumentAcl::class)->applyDoctrineExtensionConstraint($qb, $user, $requiredRole);
        //echo($qb->getDql().PHP_EOL);echo($this->showDoctrineQuery($qb->getQuery()).PHP_EOL);
        return !empty($qb->getQuery()->getResult());
    }
}
