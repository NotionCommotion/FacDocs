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

use App\ArchiveBuilder\Dto\ArchivePhysicalMediaCollection;
use JsonSerializable;

final class DocumentTreeTransformer extends AbstractTransformer implements JsonSerializable
{
    public function __construct(private ArchivePhysicalMediaCollection $archivePhysicalMediaCollection)
    {
    }

    public function jsonSerialize(): mixed
    {
        $documents = [];
        foreach ($this->archivePhysicalMediaCollection->getArchivePhysicalMedias() as $archivePhysicalMedia) {
            $archiveDocuments = $archivePhysicalMedia->getArchiveDocuments();
            if (!$archivePhysicalMedia->hasMultipleDocuments() && !$archivePhysicalMedia->nameChanged()) {
                continue;
            }
            $row = [
                'text' => $archivePhysicalMedia->getFilename(),
                'a_attr' => [
                    'href' => $archivePhysicalMedia->getZipFilepath(),
                    'target' => '_blank',
                ],
                'icon' => 'jstree-file',
                'children' => [],
            ];
            foreach ($archiveDocuments as $archiveDocument) {
                $d = $archiveDocument->getDocument();
                $row['children'][] = [
                    'text' => sprintf('%s | ID: %s - %s', $d->getFilename(), $d->getId(), $d->getSpecification()->getName()),
                    'icon' => 'jstree-checkbox',
                ];
            }
            $documents[] = $row;
        }

        return $documents;
    }
}
