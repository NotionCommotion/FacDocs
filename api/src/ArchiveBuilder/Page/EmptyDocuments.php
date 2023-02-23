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

final class EmptyDocuments extends AbstractPage
{
    public function render(Content $content): string
    {
        $emptyList = $this->makeList($this->pageService->getArchivePhysicalMediaCollection()->getEmptyDocuments());

        return <<<EOL
<h5>Empty Documents</h5>
<p>The following documents did not have any media associated with them.</p>
$emptyList
EOL;
    }
}
