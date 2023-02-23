<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use Faker\Generator as FakerGenerator;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

final class FakerProperty implements PropertyInterface
{
    private array $arguments=[];

    public function __construct(private string $name, private string $formatter, mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getValue(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed
    {
        //return $schemaFixtureService->getFakerGenerator()->{$this->formatter};
        return $schemaFixtureService->getFakerGenerator()->{$this->formatter}(...$this->arguments);
    }

    public function debug(): mixed
    {
        return $this->getName();
    }
}
