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

namespace App\ArchiveBuilder;

use App\ArchiveBuilder\Dto\ArchiveSpec;
use App\ArchiveBuilder\Dto\ArchivePhysicalMediaCollection;
use App\ArchiveBuilder\Dto\ArchiveSpecTree;
use App\Entity\Project\Project;
use Doctrine\ORM\EntityManagerInterface;

class SpecTreeBuilder
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function createSpecTree(Project $project, ArchiveSpec $archiveSpec, ArchivePhysicalMediaCollection $archivePhysicalMediaCollection): ArchiveSpecTree
    {
        $sql = <<<EOL
WITH RECURSIVE t(id, parent_id, name) AS (
    SELECT s.id, COALESCE(csi.parent_id, cs.parent_id) parent_id, s.name, csi.spec, true is_leaf
    FROM document d
    INNER JOIN specification s ON s.id=d.specification_id
    LEFT OUTER JOIN csi_specification csi ON csi.id=s.id
    LEFT OUTER JOIN custom_specification cs ON cs.id=s.id
    WHERE d.project_id = ?
    UNION
    SELECT s.id, COALESCE(csi.parent_id, cs.parent_id) parent_id, s.name, csi.spec, false is_leaf
    FROM t
    LEFT OUTER JOIN csi_specification csi ON csi.id=t.parent_id
    LEFT OUTER JOIN custom_specification cs ON cs.id=t.parent_id
    LEFT OUTER JOIN specification s ON s.id=csi.id OR s.id=cs.id
    WHERE s.id IS NOT NULL
)
SELECT id, parent_id, name, spec, is_leaf
FROM t
ORDER BY t.id
EOL;
        $stmt = $this->entityManager->getConnection()->getNativeConnection()->prepare($sql);
        $stmt->execute([$project->getId()]);
        $archiveSpecs = $this->getSpecs($stmt->fetchAll(), [0 => $archiveSpec]);

        return new ArchiveSpecTree($archivePhysicalMediaCollection, $archiveSpec, ...$archiveSpecs);
    }

    private function getSpecs(array $data, array $parents, array $archiveSpecs = []): array
    {
        foreach ($data as $i => $d) {
            $parentId = $d['parent_id'] ?? 0;
            if (isset($parents[$parentId])) {
                if (!isset($parents[$d['id']]) || $d['is_leaf']) {
                    $archiveSpec = new ArchiveSpec($d['id'], $parents[$parentId], $d['name'], $d['spec'] ?? 'NoSpec');
                    if (!isset($parents[$d['id']])) {
                        $parents[$d['id']] = $archiveSpec;
                    }
                    if ($d['is_leaf']) {
                        $archiveSpecs[$d['id']] = $archiveSpec;
                    }
                }
                unset($data[$i]);
            }
        }
        if ($data !== []) {
            // Should never happen since data is sorted by SQL.
            $archiveSpecs = $this->getSpecs($data, $parents, $archiveSpecs);
        } else {
            return array_values($archiveSpecs);
        }
    }
}
