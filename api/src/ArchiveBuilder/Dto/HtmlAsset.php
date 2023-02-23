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

use Exception;

// Used for dynamically created scripts
class HtmlAsset
{
    private array $jsScripts = [];
    private array $cssScripts = [];

    public function getJsScripts(): array
    {
        return $this->jsScripts;
    }

    public function getCssScripts(): array
    {
        return $this->cssScripts;
    }

    public function addJsScript(string $filename, string $script): self
    {
        return $this->addScript($filename, $script, 'js');
    }

    public function addCssScript(string $filename, string $script): self
    {
        return $this->addScript($filename, $script, 'css');
    }

    private function addScript(string $filename, string $script, string $type): self
    {
        $scripts = $type.'Scripts';
        if (isset($this->{$scripts}[$filename])) {
            if ($this->{$scripts}[$filename] === $script) {
                return $this;
            }
            throw new Exception(sprintf('%s already used', $filename));
        }
        $this->{$scripts}[$filename] = $script;

        return $this;
    }
}
