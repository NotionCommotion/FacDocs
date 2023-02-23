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

class ArchiveSpecTree
{
    /**
     * @var ArchiveSpec[]|mixed[]|mixed
     */
    public $archiveSpecs;
    public function __construct(ArchivePhysicalMediaCollection $archivePhysicalMediaCollection, private ArchiveSpec $rootspec, ArchiveSpec ...$archiveSpecs)
    {
        foreach ($archiveSpecs as $archiveSpec) {
            $this->archiveSpecs[$archiveSpec->getId()] = $archiveSpec;
        }

        foreach ($archivePhysicalMediaCollection->getArchiveDocuments() as $archiveDocument) {
            $this->archiveSpecs[$archiveDocument->getDocument()->getSpecification()->getId()]->addArchiveDocument($archiveDocument);
        }
    }

    public function getRootArchiveSpec(): ArchiveSpec
    {
        return $this->rootspec;
    }

    public function getArchiveSpecs(): array
    {
        return array_values($this->archiveSpecs);
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['rootArchiveSpec' => $this->getRootArchiveSpec()->debug($follow, $verbose, $hide), 'archiveSpecs' => $this->getArchiveSpecs()->debug($follow, $verbose, $hide)];
    }
}
