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

namespace App\ArchiveBuilder\Dto;

use App\ArchiveBuilder\DocumentNamer;
use App\Entity\Document\Document;

class ArchivePhysicalMediaCollection
{
    private array $archivePhysicalMedias = [];
    private array $archiveDocuments = [];
    private array $emptyDocuments = [];

    public function __construct(DocumentNamer $documentNamer, string $zipPath, Document ...$documents)
    {
        // Documents are provided in order of most commonly used filename when multiple documents use the same PhysicalMedia
        foreach ($documents as $document) {
            if (($media = $document->getMedia()) !== null) {
                $physicalMediaId = $media->getPhysicalMedia()->getId();
                if (!isset($this->archivePhysicalMedias[$physicalMediaId])) {
                    $this->archivePhysicalMedias[$physicalMediaId] = new ArchivePhysicalMedia($media->getPhysicalMedia(), $zipPath);
                }
                $archivePhysicalMedia = $this->archivePhysicalMedias[$physicalMediaId];
                $archivePhysicalMedia->addDocument($document);
            } else {
                $this->emptyDocuments[] = $document;
                $archivePhysicalMedia = null;
            }
            $this->archiveDocuments[] = new ArchiveDocument($document, $archivePhysicalMedia);
        }

        foreach ($this->archivePhysicalMedias as $archivePhysicalMedia) {
            $archivePhysicalMedia->setFilename($documentNamer->getFilename($archivePhysicalMedia));
        }
    }

    public function getArchivePhysicalMedias(): array
    {
        return array_values($this->archivePhysicalMedias);
    }

    public function getArchiveDocuments(): array
    {
        return $this->archiveDocuments;
    }

    public function getEmptyDocuments(): array
    {
        return $this->emptyDocuments;
    }
}
