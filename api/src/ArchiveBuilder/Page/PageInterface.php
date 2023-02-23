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
use App\ArchiveBuilder\Dto\HtmlPage;

interface PageInterface
{
    public function setHtmlPage(HtmlPage $htmlPage): self;

    public function render(Content $content): string;
}
