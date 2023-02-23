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

use App\ArchiveBuilder\Dto\AbstractHtml;
use App\ArchiveBuilder\Dto\HtmlPage;
use App\ArchiveBuilder\Dto\PageService;
use JsonSerializable;
use Stringable;

abstract class AbstractPage extends AbstractHtml implements PageInterface, Stringable
{
    private HtmlPage $htmlPage;

    public function __construct(protected PageService $pageService)
    {
    }

    public function setHtmlPage(HtmlPage $htmlPage): self
    {
        $this->htmlPage = $htmlPage;

        return $this;
    }

    public function __toString()
    {
        return 'fixme';
    }

    protected function createJsTreeFile(JsonSerializable $jsonSerializable, string $filename): self
    {
        $content = <<<EOL
function getTree() {
    return {{ tree }};
}
EOL;
        $treeJson = $this->parseTemplate($content, [
            'tree' => json_encode($jsonSerializable, JSON_THROW_ON_ERROR),
        ]);
        $this->pageService->addJsScript($filename, $treeJson);
        $this->htmlPage->addJsAsset($filename);

        return $this;
    }
}
