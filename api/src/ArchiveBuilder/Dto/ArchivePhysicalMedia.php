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
use App\Entity\Document\PhysicalMedia;
use Exception;

class ArchivePhysicalMedia
{
    private array $archiveDocuments = [];
    private ?string $filename = null;
    private ?bool $nameChanged = null;

    public function __construct(private PhysicalMedia $physicalMedia, private string $zipPath)
    {
    }

    public function getPhysicalMedia(): PhysicalMedia
    {
        return $this->physicalMedia;
    }

    public function addDocument(Document $document): self
    {
        return $this->addArchiveDocument(new ArchiveDocument($document, $this));
    }

    public function addArchiveDocument(ArchiveDocument $archiveDocument): self
    {
        $this->archiveDocuments[] = $archiveDocument;

        return $this;
    }

    public function getArchiveDocuments(): array
    {
        return $this->archiveDocuments;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getZipFilepath(): ?string
    {
        return $this->filename ? sprintf('%s/%s', $this->zipPath, $this->filename) : null;
    }

    public function setFilename(string $filename): self
    {
        if (null !== $this->filename) {
            throw new Exception('filename cannot be changed');
        }
        $this->filename = $filename;
        $nameChanged = false;
        foreach ($this->archiveDocuments as $archiveDocument) {
            if ($archiveDocument->getDocument()->getFilename() !== $filename) {
                $nameChanged = true;
                break;
            }
        }
        $this->nameChanged = $nameChanged;

        return $this;
    }

    public function hasMultipleDocuments(): bool
    {
        return \count($this->archiveDocuments) > 1;
    }

    public function nameChanged(): ?bool
    {
        return $this->nameChanged;
    }
}
