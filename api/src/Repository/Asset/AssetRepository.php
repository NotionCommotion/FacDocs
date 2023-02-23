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

namespace App\Repository\Asset;

use PDO;
use App\Entity\Asset\Asset;
use App\Entity\Document\Document;
use App\Entity\User\BasicUserInterface;
use App\Entity\Asset\AssetInterface;
use App\Exception\RecursiveAssetException;
use App\Exception\RecursiveParentChildAssetException;
use App\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class AssetRepository extends AbstractRepository
{
    public function add(Asset $asset, bool $flush = false): void
    {
        $this->getEntityManager()->persist($asset);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Asset $asset, bool $flush = false): void
    {
        $this->getEntityManager()->remove($asset);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? Asset::class);
    }

    public function getChildren(Ulid $ulid): array
    {
        return $this->createQueryBuilder('a')
        ->join('a.parents', 'p')
        ->andWhere('p.id = :id')
        ->setParameter('id', $ulid, 'ulid')
        ->getQuery()
        ->getResult();
    }

    public function getParents(Ulid $ulid): array
    {
        return $this->createQueryBuilder('a')
        ->join('a.children', 'c')
        ->andWhere('c.id = :id')
        ->setParameter('id', $ulid, 'ulid')
        ->getQuery()
        ->getResult();
    }

    public function checkDocumentAccess(string $method, BasicUserInterface $user, Document $document): bool
    {
        // Check if any asset allows user to perform given method
        // Not complete.
        return false;
    }

    /*
    Not complete.  See https://dwbi1.wordpress.com/2017/10/18/hierarchy-with-multiple-parents/
    */
    public function debugAssetLevels(AssetInterface $asset): string
    {
        return <<<EOT
WITH cte (child_node, parent_node, path)
     AS (SELECT child_node,
                parent_node,
                Cast(child_node AS VARCHAR(max)) AS path
         FROM   parent_child_hierarchy
         WHERE  parent_node IS NULL
         UNION ALL
         SELECT p.child_node AS child,
                t.child_node AS parent,
                t.path + ' > ' + Cast(p.child_node AS VARCHAR(50)) AS path
         FROM   parent_child_hierarchy p
                JOIN cte t ON p.parent_node = t.child_node)
SELECT path
INTO   #path
FROM   cte
WHERE  parent_node IS NOT NULL
EOT;
    }

    public function validate(Asset $parentAsset): void
    {
        // throw exception if is recurive (i.e. parentAsset has any parent assets which are also child assets).
        // For CTE, consider using https://github.com/somnambulist-tech/cte-builder
        // NOT COMPLETE!!!!!!!
        return;
        $sql = <<<EOT
WITH RECURSIVE results AS (
    SELECT parent_id, child_id
    FROM asset_parents_have_children
    WHERE parent_id = ?
    UNION
        SELECT aphc.parent_id, aphc.child_id
        FROM results
        LEFT OUTER JOIN asset_parents_have_children aphc ON aphc.parent_id = results.child_id
)
SELECT parent_id AS asset FROM results WHERE results.child_id IS NOT NULL;
EOT;
        $sql = <<<EOT
WITH RECURSIVE results AS (
    SELECT a.id, aphc_p.parent_id, aphc_c.child_id
    FROM asset a
    INNER JOIN asset_parents_have_children aphc_p ON aphc_p.parent_id = a.id
    INNER JOIN asset_parents_have_children aphc_c ON aphc_c.child_id = a.id
    WHERE
        a.id = ?
    UNION
        SELECT results.id, aphc_p.parent_id, aphc_c.child_id
        FROM results
        LEFT OUTER JOIN asset_parents_have_children aphc_p ON aphc_p.parent_id = results.parent_id
        LEFT OUTER JOIN asset_parents_have_children aphc_c ON aphc_c.child_id = results.child_id
)
SELECT id, parent_id, child_id FROM results WHERE results.parent_id IS NOT NULL AND results.child_id IS NOT NULL;
EOT;
        //echo($parentAsset.PHP_EOL);exit($sql);
        $stmt = $this->getEntityManager()->getConnection()
        ->getNativeConnection()   // Something changed and no longer needed???
        ->prepare($sql);
        $stmt->execute([$parentAsset->getId()->toRfc4122()]);
        if ($recursiveIds = $stmt->fetchAll()) {
            //print_r($recursiveIds);exit;
            throw new RecursiveAssetException($parentAsset, ...$this->createQueryBuilder('a')->andWhere('a.id = :ids')->setParameter('ids', $recursiveIds)->getQuery()->getResult());
        }
    }

    public function validateParentChild(Asset $parentAsset, Asset $childAsset): void
    {
        // throw exception if is recurive (i.e. $parentAsset is a child of $childAsset).
        // This only gets called by ParentAssetAddChildAssetProcessor. Otherwise, use self::validate()???
        $sql = <<<EOT
WITH RECURSIVE results AS (
    SELECT parent_id, child_id
    FROM asset_parents_have_children
    WHERE parent_id = ?
    UNION
        SELECT aphc.parent_id, aphc.child_id
        FROM results
        LEFT OUTER JOIN asset_parents_have_children aphc ON aphc.parent_id = results.child_id
)
SELECT parent_id AS asset FROM results WHERE results.child_id =?;
EOT;
        $stmt = $this->getEntityManager()->getConnection()
        ->getNativeConnection()
        ->prepare($sql);
        $stmt->execute([$parentAsset->getId()->toRfc4122(), $childAsset->getId()->toRfc4122()]);
        if ($recursiveIds = $stmt->fetchAll(PDO::FETCH_COLUMN)) {
            throw new RecursiveParentChildAssetException($parentAsset, $childAsset, ...$this->createQueryBuilder('a')->andWhere('a.id = :ids')->setParameter('ids', $recursiveIds)->getQuery()->getResult());
        }
    }
}
