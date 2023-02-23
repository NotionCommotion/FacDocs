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

namespace App\ArchiveBuilder\Page\Transformer;

use App\ArchiveBuilder\Dto\ArchiveSpec;
use App\ArchiveBuilder\Dto\ArchiveSpecTree;
use JsonSerializable;

final class SpecTreeTransformer extends AbstractTransformer implements JsonSerializable
{
    public function __construct(private ArchiveSpecTree $archiveSpecTree)
    {
    }

    public function jsonSerialize(): mixed
    {
        return $this->getJsTree(...$this->archiveSpecTree->getRootArchiveSpec()->getChildren());
    }

    private function getJsTree(ArchiveSpec ...$archiveSpec): array
    {
        $tree = [];
        foreach ($archiveSpec as $singleArchiveSpec) {
            $row = ['id' => $singleArchiveSpec->getSpec(), 'text' => sprintf('%s %s', $singleArchiveSpec->getName(), $singleArchiveSpec->getSpec())];
            $children = [];
            foreach ($singleArchiveSpec->getArchiveDocuments() as $archiveDocument) {
                $children[] = $this->documentToArray($archiveDocument);
            }
            if ($childrenSpecs = $singleArchiveSpec->getChildren()) {
                $children = array_merge($children, $this->getJsTree(...$childrenSpecs));
            }
            if ($children !== []) {
                $row['children'] = $children;
            }
            $tree[] = $row;
        }

        return $tree;
    }
}
