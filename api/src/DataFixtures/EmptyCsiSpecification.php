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

class EmptyCsiSpecification extends CsiSpecification
{
    private $type;

    public function __construct(CsiSpecification $csiSpecification)
    {
        $this->setTitle('EmptyCsiSpecification');
        if($csiSpecification->getType()->isSection()){
            $this->setDivision($csiSpecification->getDivision());
            $this->setSection('00');
            $this->type = SpecificationType::Division;
        }
        elseif($csiSpecification->getType()->isScope()){
            $this->setDivision($csiSpecification->getDivision());
            $this->setSection($csiSpecification->getSection());
            $this->setScope('00');
            $this->type = SpecificationType::Section;
        }
        elseif($csiSpecification->getType()->IsSubscope()){
            $this->setDivision($csiSpecification->getDivision());
            $this->setSection($csiSpecification->getSection());
            $this->setScope($csiSpecification->getScope());
            $this->setSubscope(null);
            $this->type = SpecificationType::Scope;
        }
        else {
            throw new Exception(sprintf('EmptyCsiSpecification does not support type %s', $csiSpecification->getType()->name));
        }
        $this->setSpecFromString($this->getFormatedSpec());
    }

    public function getType(): SpecificationType
    {
        return $this->type;
    }

    public function getIndex(): string
    {
        return $this->{'get'.ucfirst((string) $this->type->name)}();
    }

    public function isDivision(): bool
    {
        return $this->type->isDivision();
    }

    public function isSection(): bool
    {
        return $this->type->isSection();
    }

    public function isScope(): bool
    {
        return $this->type->isScope();
    }
}
