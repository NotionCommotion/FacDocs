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

use App\ArchiveBuilder\Dto\ArchiveDocument;

abstract class AbstractTransformer
{
    protected function documentToArray(ArchiveDocument $archiveDocument): array
    {
        return [
            'text' => $archiveDocument->getDocument()->getFilename(),
            'icon' => 'jstree-file',
            'a_attr' => [
                'href' => $archiveDocument->getArchivePhysicalMedia()->getZipFilepath(),
                'target' => '_blank',
            ],
        ];
    }
}
