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

namespace App\Entity\ListRanking;

use Attribute;
use ReflectionProperty;

/**
 * Placed on entity class with no arguements.
 * Placed on entity property with optional arguement method: getterName, and otherwize getPropertyName() will be used.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)] // | Attribute::IS_REPEATABLE
class RankedListAttribute
{
    public function __construct(public $method = null)
    {
    }

    public function getValue(object $object, ReflectionProperty $reflectionProperty): RankedListInterface
    {
        // exit($this->method??'get'.ucfirst($propReflect->getName()));
        return $object->{$this->method ?? 'get'.ucfirst($reflectionProperty->getName())}();
    }
}
