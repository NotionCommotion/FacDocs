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

namespace App\Entity\Specification;

enum SpecificationType
{
    case Root;
    case Division;
    case Section;
    case Scope;
    case SubScope;

    public static function fromName(string $name): self
    {
        return \constant('self::'.$name);
    }

    public function isRoot(): bool
    {
        return $this->name === 'Root';
    }

    public function isDivision(): bool
    {
        return $this->name === 'Division';
    }

    public function isSection(): bool
    {
        return $this->name === 'Section';
    }

    public function isScope(): bool
    {
        return $this->name === 'Scope';
    }

    public function isSubScope(): bool
    {
        return $this->name === 'SubScope';
    }
    
    public function getIndex(CsiSpecification $specification):string
    {
        // returns index relative to its parent.  i.e. 230000 => 23, 230900 => 09, 230923 => 23, 230923.19 => 19
        return match ($this->name) {
            'Division' => $specification->getDivision(),
            'Section' => $specification->getSection(),
            'Scope' => $specification->getScope(),
            'SubScope' => $specification->getSubscope(),
            default => throw new \Exception(sprintf('getChild() does not support type %s', $this->name)),
        };
    }
}
