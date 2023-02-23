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

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
// use ApiPlatform\Processor\ResumableProcessorInterface;
use App\ArchiveBuilder\ArchiveBuilderService;
use App\Entity\Archive\Archive;

class ArchiveDeleterDecoratedProcessor implements ProcessorInterface
{
    public function process($archive, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        unlink($archive->getFilename());
        return null;
    }
}
