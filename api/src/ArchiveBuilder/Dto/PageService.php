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

class PageService
{
    public function __construct(private HtmlPageCollection $htmlPageCollection, private ArchivePhysicalMediaCollection $archivePhysicalMediaCollection, private ArchiveSpecTree $archiveSpecTree)
    {
    }

    public function HtmlPageCollection(): HtmlPageCollection
    {
        return $this->htmlPageCollection;
    }

    public function getArchivePhysicalMediaCollection(): ArchivePhysicalMediaCollection
    {
        return $this->archivePhysicalMediaCollection;
    }

    public function getArchiveSpecTree(): ArchiveSpecTree
    {
        return $this->archiveSpecTree;
    }

    public function addJsScript(string $filename, string $script): self
    {
        $this->htmlPageCollection->addJsScript($filename, $script);

        return $this;
    }

    public function addCssScript(string $filename, string $script): self
    {
        $this->htmlPageCollection->addCssScript($filename, $script);

        return $this;
    }
}
