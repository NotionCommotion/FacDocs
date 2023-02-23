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

use App\ArchiveBuilder\Page\Transformer\DocumentTreeTransformer;
use App\ArchiveBuilder\Dto\Content;
use App\ArchiveBuilder\Dto\HtmlPage;

final class DocumentTree extends AbstractPage
{
    private const JSON_JS_FILE = 'assets/js/document-tree-json.js';

    public function setHtmlPage(HtmlPage $htmlPage): self
    {
        return parent::setHtmlPage($htmlPage)
        ->createJsTreeFile(new DocumentTreeTransformer($this->pageService->getArchivePhysicalMediaCollection()), self::JSON_JS_FILE);
    }

    public function render(Content $content): string
    {
        return <<<EOL
<div id="tree"></div>
EOL;
    }
}
