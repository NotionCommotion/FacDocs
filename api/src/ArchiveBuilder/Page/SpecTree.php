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

use App\ArchiveBuilder\Page\Transformer\SpecTreeTransformer;
use App\ArchiveBuilder\Dto\Content;
use App\ArchiveBuilder\Dto\HtmlPage;

final class SpecTree extends AbstractPage
{
    private const JSON_JS_FILE = 'assets/js/spec-tree-json.js';

    public function setHtmlPage(HtmlPage $htmlPage): self
    {
        return parent::setHtmlPage($htmlPage)
        ->createJsTreeFile(new SpecTreeTransformer($this->pageService->getArchiveSpecTree()), self::JSON_JS_FILE);
    }

    public function render(Content $content): string
    {
        return <<<EOL
<div id="tree"></div>
EOL;
    }
}
