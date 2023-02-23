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

namespace App\ArchiveBuilder\Page;

use App\ArchiveBuilder\Dto\Content;

final class DocumentList extends AbstractPage
{
    public function render(Content $content): string
    {
        $documentTable = $this->makeTable($this->getDocumentArray(), ['ID', 'Filename', 'Specification', 'Type', 'Stage'], 'files', 'table');

        return <<<EOL
<h5>Files</h5>
$documentTable
EOL;
    }

    private function getDocumentArray(): array
    {
        $documents = [];
        foreach ($this->pageService->getArchivePhysicalMediaCollection()->getArchiveDocuments() as $archiveDocument) {
            $d = $archiveDocument->getDocument();
            $documents[] = [
                'id' => $d->getId(),
                'filename' => sprintf('<a href="%s"-1" target="_blank">%s</a>', $archiveDocument->getArchivePhysicalMedia()->getZipFilepath(), $d->getFilename()),
                'specification' => $d->getSpecification()->getName(),
                'documentType' => $d->getDocumentType()->getName(),
                'documentStage' => $d->getDocumentStage()->getName(),
            ];
        }

        return $documents;
    }
}
