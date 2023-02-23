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

class HtmlMenu extends AbstractHtml
{
    private array $htmlPages = [];
    private array $menus = [];

    public function __construct(HtmlPage ...$htmlPages)
    {
        $this->htmlPages = $htmlPages;
        foreach ($htmlPages as $htmlPage) {
            $this->menus[] = [$htmlPage->getName(), $htmlPage->getFilename()];
        }
    }

    public function render(HtmlPage $activePage): string
    {
        $menu = [];
        // ['name', 'link'='#', blank=false]
        foreach ($this->htmlPages as $htmlPage) {
            $menu[] = sprintf(
                '<li class="nav-item"><a class="nav-link%s" href="%s"%s>%s</a></li>',
                $htmlPage === $activePage ? ' active' : '',
                $htmlPage->getFileName(),
                $htmlPage->isBlank() ? ' target="_blank"' : '',
                $htmlPage->getName()
            );
        }

        return implode(\PHP_EOL, $menu);
    }

    public function getMenus():array
    {
        return $this->menus;
    }
}
