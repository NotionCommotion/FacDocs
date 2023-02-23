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

use App\ArchiveBuilder\Page\PageInterface;
use App\Entity\Archive\Template;

class HtmlPage extends AbstractHtml
{
    public function __construct(
        private PageInterface $page,
        private string $pageName,
        private string $fileName,
        private array $jsAssets,
        private array $cssAssets,
        private Content $content,
        private string $htmlWrapper,
        private Template $template,
        private bool $isBlank = false,
    ) {
        $page->setHtmlPage($this);
    }

    public function getName(): string
    {
        return $this->pageName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function isBlank(): bool
    {
        return $this->isBlank;
    }

    public function render(HtmlMenu $htmlMenu, ?bool $beautify = null): string
    {
        $pageName = $this->getName();
        $content = [
            'title' => sprintf('%s - %s', $this->content->getValue('project.name'), $pageName),
            'menu' => $htmlMenu->render($this),
            'userTemplate' => $this->parseTemplate($this->template->getHtml(), $this->content->getValues()),
            'content' => $this->page->render($this->content),
            'js' => implode(\PHP_EOL, $this->getJsAssets()),
            'css' => implode(\PHP_EOL, $this->getCssAssets()),
        ];
        $html = $this->parseTemplate($this->htmlWrapper, $content);
        if (true === $beautify) {
            $html = $this->beautifyHtml($html);
        } elseif (false === $beautify) {
            $html = $this->minimizeHtml($html);
        }

        return $html;
    }

    public function addValue(string $name, string|int|float|null $value): self
    {
        $property = null;
        $this->content->addValue($property, $value);

        return $this;
    }

    public function addValues(array $nameValues): self
    {
        $this->content->addValues($nameValues);

        return $this;
    }

    public function addJsAsset(string $asset): self
    {
        $this->jsAssets[] = $asset;

        return $this;
    }

    public function addCssAsset(string $asset): self
    {
        $this->cssAssets[] = $asset;

        return $this;
    }

    public function addJsAssets(array $assets): self
    {
        $this->jsAssets = array_merge($this->jsAssets, $assets);

        return $this;
    }

    public function addCssAssets(array $assets): self
    {
        $this->cssAssets = array_merge($this->cssAssets, $assets);

        return $this;
    }

    public function getJsAssets(): array
    {
        return $this->jsLinks(array_unique($this->jsAssets));
    }

    public function getCssAssets(): array
    {
        return $this->cssLinks(array_unique($this->cssAssets));
    }
}
