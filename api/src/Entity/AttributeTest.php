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
// App\DataProvider\TestAttributeDataProvider and App\Entity\AttributeTest is not part of this application,
// but just used to export meta data to be used in documentation.

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\GetCollection;
use JsonSerializable;

#[ApiResource(operations: [new NotExposed, new GetCollection()])]
class AttributeTest implements JsonSerializable
{
    public function __construct(#[ApiProperty(identifier: true)] public string $className, public array $classAttributes, public array $propertyAttributes, public array $methodAttributes, public array $extraDoctrineProperties, public array $extraReflectionProperties, public ?string $description = null)
    {
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
