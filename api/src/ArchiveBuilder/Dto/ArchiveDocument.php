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

use App\Entity\Document\Document;

class ArchiveDocument
{
    public function __construct(private Document $document, private ?ArchivePhysicalMedia $archivePhysicalMedia)
    {
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getDocumentArray(): array
    {
        return [
            'id' => $this->document->getId(),
            'filename' => $this->document->getFilename(),
            'specification' => $this->document->getSpecification()->getName(),
            'documentType' => $this->document->getDocumentType()->getName(),
            'documentStage' => $this->document->getDocumentStage()->getName(),
        ];
    }

    public function getArchivePhysicalMedia(): ?ArchivePhysicalMedia
    {
        return $this->archivePhysicalMedia;
    }

    public function nameChanged(): bool
    {
        return $this->archivePhysicalMedia && $this->archivePhysicalMedia->getFilename() !== $this->document->getFilename();
    }
}
