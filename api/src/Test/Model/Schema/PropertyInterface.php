<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

interface PropertyInterface
{
    public function getName():string;
    public function getValue(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed;

}
