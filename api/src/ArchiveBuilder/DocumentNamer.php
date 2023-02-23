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

use App\ArchiveBuilder\Dto\ArchivePhysicalMedia;
class DocumentNamer
{
    private array $usedFilenames = [];

    public function getFilename(ArchivePhysicalMedia $archivePhysicalMedia): ?string
    {
        if ($filename = $archivePhysicalMedia->getFilename()) {
            return $filename;
        }
        $archiveDocuments = $archivePhysicalMedia->getArchiveDocuments();
        if (empty($archiveDocuments)) {
            return null;
        }
        $filenames = [];
        $allFilenames = [];
        foreach ($archiveDocuments as $archiveDocument) {
            $filename = $archiveDocument->getDocument()->getMedia()->getFilename();
            if (!\in_array($filename, $this->usedFilenames, true)) {
                $filenames[$filename] = ($filenames[$filename] ?? 0) + 1;
            }
            $allFilenames[$filename] = ($allFilenames[$filename] ?? 0) + 1;
        }
        if ($filenames !== []) {
            asort($filenames);
            $filename = array_key_first($filenames);
        } else {
            asort($allFilenames);
            $filename = $this->createNewFilename(array_key_first($allFilenames));
        }

        return $filename;
    }

    private function createNewFilename(string $filename): string
    {
        $pi = pathinfo($filename);
        $i = 1;
        while (true) {
            $filename = sprintf('%s_R%s.%s', $pi['filename'], $i, $pi['extension']);

            if (!\in_array($filename, $this->usedFilenames, true)) {
                return $filename;
            }
            ++$i;
        }
    }
}
