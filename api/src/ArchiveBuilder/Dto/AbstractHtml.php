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

use DOMDocument;
abstract class AbstractHtml
{
    public array $jsScripts;
    public array $cssScripts;
    protected function parseTemplate(string $template, array $content = []): string
    {
        if ($content !== []) {
            $keys = array_map(fn($a) => '{{ '.$a.' }}', array_keys($content));

            return str_replace($keys, array_values($content), $template);
        }

        return $template;
    }

    protected function parsePhpTemplate(string $phpFile, array $content = []): string
    {
        ob_start();
        require $phpFile;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    protected function createMenu(array $menu, string $active = null): string
    {
        // ['name', 'link'='#', blank=false]
        foreach ($menu as $i => $m) {
            $menu[$i] = sprintf('<li class="nav-item"><a class="nav-link%s" href="%s"%s>%s</a></li>', $m[0] === $active ? ' active' : '', $m[1], isset($m[2]) ? ' target="_blank"' : '', $m[0]);
        }

        return implode(\PHP_EOL, $menu);
    }

    protected function makeTable(array $rows, array $header = [], string $id = null, string ...$classes): string
    {
        foreach ($rows as &$row) {
            $row = '<tr>'.implode('', array_map(fn($v) => '<td>'.$v.'</td>', $row)).'</tr>';
        }
        if ($header !== []) {
            // exit($t);
            array_unshift($rows, '<tr>'.implode('', array_map(fn($v) => '<th>'.$v.'</th>', $header)).'</tr>');
        }

        return sprintf(
            '<table%s%s>%s</table>',
            $id ? " id='$id'" : null,
            $classes !== [] ? sprintf(' class="%s"', implode(' ', $classes)) : null,
            implode(\PHP_EOL, $rows)
        );
    }

    protected function makeList(array $rows, bool $ordered = false, string $id = null, string ...$classes): string
    {
        return sprintf(
            $ordered ? '<ol%s%s>%s</ol>' : '<ul%s%s>%s</ul>',
            $id ? " id='$id'" : null,
            $classes !== [] ? sprintf(' class="%s"', implode(' ', $classes)) : null,
            implode(\PHP_EOL, array_map(fn($v) => '<li>'.$v.'</li>', $rows))
        );
    }

    // Add itegrety to links?
    protected function jsLink(string $filename): array
    {
        return sprintf('<script src="%s"></script>', $this->jsScripts[$filename]);
    }

    protected function cssLink(string $filename): string
    {
        return sprintf('<link href="%s" rel="stylesheet" />', $this->cssScripts[$filename]);
    }

    protected function jsLinks(array $filenames): array
    {
        return $this->createLinks($filenames, '<script src="%s"></script>');
    }

    protected function cssLinks(array $filenames): array
    {
        return $this->createLinks($filenames, '<link href="%s" rel="stylesheet" />');
    }

    private function createLinks(array $filenames, string $template): array
    {
        return array_map(fn(string $filename) => sprintf($template, $filename), $filenames);
    }

    protected function beautifyHtml(string $html): string
    {
        $domDocument = new DOMDocument();
        $domDocument->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $domDocument->loadHTML($html, \LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        $domDocument->formatOutput = true;

        return $domDocument->saveXML($domDocument->documentElement);
    }

    protected function minimizeHtml(string $html): string
    {
        $search = [
            '/\>[^\S ]+/s',     // Remove whitespaces after tags
            '/[^\S ]+\</s',     // Remove whitespaces before tags
            '/(\s)+/s',         // Remove multiple whitespace sequences
            '/<!--(.|\s)*?-->/', // Removes comments
        ];

        return preg_replace($search, ['>', '<', '\\1'], $html);
    }
}
