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

namespace App\Repository\DocumentGroup;

use App\Entity\Document\Document;
use App\Entity\DocumentGroup\DocumentGroup;
use App\Entity\User\BasicUserInterface;
use App\Repository\AbstractRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentGroup[]    findAll()
 * @method DocumentGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentGroupRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, DocumentGroup::class);
    }

    public function checkDocumentAccess(string $method, BasicUserInterface $user, Document $document): bool
    {
        // Check if any asset allows user to perform given method
        // Not complete.
        return false;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DocumentGroup $documentGroup, bool $flush = true): void
    {
        $this->_em->persist($documentGroup);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(DocumentGroup $documentGroup, bool $flush = true): void
    {
        $this->_em->remove($documentGroup);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
