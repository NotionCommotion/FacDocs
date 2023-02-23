<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use JsonSerializable;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

final class Response implements JsonSerializable
{
    private array $properties=[];

    public function __construct(Ulid $id, SchemaFixtureService $schemaFixtureService, Request $request)
    {
        //$this->classMap[$metadata->getTablename().'s'] = $metadata->getName();
        foreach($request->getProperties() as $property) {
            try {
                if(!$schemaFixtureService->isExcludedProperty($request, $property)) {
                    $this->properties[$property->getName()] = $property->getValue($id, $schemaFixtureService);
                }
                else {
                    throw new \Exception('This should never happen.');
                }
            }
            catch(InvalidPropertyException $e) {
                // Don't set property.
                //echo(__METHOD__.' Do not set property!!!!!!!!!!'.PHP_EOL);
                //print_r(array_merge($request->debug(), ['property'=>$property->debug()]));
            }
        }
    }

    public function toArray()
    {
        return $this->properties;
    }

    public function jsonSerialize():mixed
    {
        return $this->properties;
    }
}
