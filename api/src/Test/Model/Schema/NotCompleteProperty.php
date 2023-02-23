<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

final class NotCompleteProperty implements PropertyInterface
{
    public function __construct(private string $name)
    {
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getValue(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed
    {
        throw new InvalidPropertyException('fix_this_link');
    }

    public function debug(): mixed
    {
        return $this->getName();
    }
}
