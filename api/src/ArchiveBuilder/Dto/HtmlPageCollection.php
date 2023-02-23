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

use Generator;
use Exception;

class HtmlPageCollection extends AbstractHtml
{
    private array $pages = [];

    public function __construct(
        private HtmlAsset $htmlAsset,
    ) {
    }

    public function addPage(HtmlPage $htmlPage): self
    {
        $name = $htmlPage->getName();
        if (isset($this->pages[$name])) {
            throw new Exception(sprintf('%s already exists', $name));
        }
        $this->pages[$name] = $htmlPage;

        return $this;
    }

    public function getPage(string $pageName): HtmlPage
    {
        return $this->pages[$pageName];
    }

    public function addJsScript(string $filename, string $script): self
    {
        $this->htmlAsset->addJsScript($filename, $script);

        return $this;
    }

    public function addCssScript(string $filename, string $script): self
    {
        $this->htmlAsset->addCssScript($filename, $script);

        return $this;
    }

    public function getJsScripts(): array
    {
        return $this->htmlAsset->getJsScripts();
    }

    public function getCssScripts(): array
    {
        return $this->htmlAsset->getCssScripts();
    }

    public function generatePages(?bool $beautify = null): Generator
    {
        $htmlMenu = new HtmlMenu(...array_values($this->pages));
        foreach ($this->pages as $page) {
            yield $page->getFileName() => $page->render($htmlMenu);
        }
    }
}
