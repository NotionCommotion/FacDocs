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

namespace App\DataFixtures;

use App\Entity\Specification\CsiSpecification;
use App\Entity\Specification\SpecificationType;
use Doctrine\Persistence\ObjectManager;
use JsonSerializable;
use Exception;

class SpecHelper implements JsonSerializable
{
    private const MAX_INSERTS = 1000;

    private SpecificationType $type;
    private string $index;
    private self $parent;
    private array $children = [];

    public static function create(string $title, string $spec, self $ancestor): self
    {
        if ($title !== utf8_encode($title)) {
            printf('CsiSpecificationRepository encode %s to %s'.\PHP_EOL, $title, utf8_encode($title));
            $title = utf8_encode($title);
        }

        return new self(CsiSpecification::create($title, $spec), $ancestor);
    }

    public function __construct(private CsiSpecification $csiSpecification, ?self $ancestor = null)
    {
        if ($ancestor !== null) {
            $this->type = $csiSpecification->getType();
            $this->index = $csiSpecification->getIndex();
            $this->parent = $this->findParent($ancestor)->addChild($this);
        } else {
            $this->type = SpecificationType::Root;
        }
    }

    public function findParent(self $ancestor): self
    {
        return ($this->type->isDivision() && $ancestor->getType()->isRoot()) || $this->csiSpecification->isChild($ancestor->getSpec())
        ? $ancestor
        : $this->findParent($ancestor->getChild($this));
    }

    public function getChild(self $grandchild): self
    {
        $grandchildSpec = $grandchild->getSpec();
        $index = match ($this->type->name) {
            'Root' => $grandchildSpec->getDivision(),
            'Division' => $grandchildSpec->getSection(),
            'Section' => $grandchildSpec->getScope(),
            default => throw new Exception(sprintf('getChild() does not support type %s', $this->type->name)),
        };

        return $this->children[$index] ?? new self(new EmptyCsiSpecification($grandchildSpec, $this->type), $this);
    }

    public function addChild(self $child): self
    {
        if (isset($this->children[$child->getIndex()]) && !$this->children[$child->getIndex()] instanceof EmptyCsiSpecification) {
            // Only needed if previously EmptyCsiSpecification was used (not needed if sorted correctly), and updates self with real spec
            throw new Exception(sprintf('Child %s %s already set', $this->children[$child->getIndex()]->getFormatedSpec(), $this->children[$child->getIndex()]->getSpec()->getTitle()));
        }
        $this->children[$child->getIndex()] = $child;

        return $this;
    }

    public function getSpec(): CsiSpecification
    {
        return $this->csiSpecification;
    }

    public function getParent(): self
    {
        return $this->parent;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getType(): SpecificationType
    {
        return $this->type;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getFormatedSpec(): string
    {
        return (($spec = trim($this->csiSpecification->getFormatedSpec())) !== '' && ($spec = trim($this->csiSpecification->getFormatedSpec())) !== '0') ? $spec : 'N/A';
    }

    public function setSpecParents(array $log = ['emptyParent' => [], 'emptyChild' => [], 'division' => []]): array
    {
        $parent = $this;
        while ($parent->getSpec() instanceof EmptyCsiSpecification) {
            $originalParent = $parent;
            $parent = $parent->getParent();
            $log['emptyParent'][] = sprintf('%10s replaced by %15s %s', $originalParent->getFormatedSpec(), $parent->getFormatedSpec(), $parent->getSpec()->getTitle());
        }
        foreach ($this->children as $child) {
            if ($this->type->isRoot()) {
                $log['division'][] = sprintf('%15s %s', $child->getSpec()->getFormatedSpec(), $child->getSpec()->getTitle());
            } elseif ($child->getSpec() instanceof EmptyCsiSpecification) {
                $log['emptyChild'][] = sprintf('child:  %10s    parent: %15s %s', $child->getSpec()->getFormatedSpec(), $this->csiSpecification->getFormatedSpec(), $this->csiSpecification->getTitle());
            } else {
                $child->getSpec()->setParent($parent->getSpec());
            }
            $log = $child->setSpecParents($log);
        }

        return $log;
    }

    public function getCount(): int
    {
        $count = 0;
        if (!$this->csiSpecification instanceof EmptyCsiSpecification && !$this->type->isRoot()) {
            ++$count;
        }
        foreach ($this->children as $child) {
            $count += $child->getCount();
        }

        return $count;
    }

    public function persist(ObjectManager $objectManager, int &$counter=0): int
    {
        if (!$this->csiSpecification instanceof EmptyCsiSpecification && !$this->type->isRoot()) {
            $objectManager->persist($this->csiSpecification);
        }
        foreach ($this->children as $child) {
            $child->persist($objectManager, $counter);
            if($counter % self::MAX_INSERTS === 0) {
                printf('Spec intermidiate flush %d (%d records)'.PHP_EOL, $counter/self::MAX_INSERTS, $counter);
                $objectManager->flush();
            }
            $counter++;
        }
        $children = [];
        return $counter;
    }

    public function debug(string $padding = ''): void
    {
        printf('%s%s %s'.\PHP_EOL, $padding, $this->csiSpecification->getFormatedSpec(), $this->csiSpecification->getTitle());
        foreach ($this->children as $child) {
            $child->debug($padding.'  ');
        }
    }

    public function jsonSerialize(): mixed
    {
        // printf('Name: %s'.PHP_EOL, $this->spec->getTitle());
        // printf('count(children): %s'.PHP_EOL, count($this->children));
        $arr = [
            'type' => $this->type->name,
            'index' => $this->index,
            'children' => $this->children,
            'title' => $this->csiSpecification->getTitle(),
            'division' => $this->csiSpecification->getDivision(),
        ];

        return $this->type->isRoot() ? $arr : array_merge($arr, [
            'section' => $this->csiSpecification->getSection(),
            'scope' => $this->csiSpecification->getScope(),
            'subScope' => $this->csiSpecification->getSubscope(),
            'spec' => $this->csiSpecification->getSpec(),
        ]);
    }
}
