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

class ArchiveSpec
{
    private array $archiveDocuments = [];
    private array $children = [];

    public function __construct(private int $id, private ?self $parent, private string $name, private ?string $spec)
    {
        if ($parent !== null) {
            $parent->addChild($this);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpec(): ?string
    {
        return $this->spec;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addArchiveDocument(ArchiveDocument $archiveDocument): self
    {
        $this->archiveDocuments[] = $archiveDocument;

        return $this;
    }

    public function getArchiveDocuments(): array
    {
        return $this->archiveDocuments;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id' => $this->id, 'name' => $this->name, 'spec' => $this->spec, 'children' => $this->children];
    }
}
