<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use ArrayObject;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

final class Request
{
    private array $properties=[];

    public function __construct(private string $class, private string $tableName, private array $identifier, private bool $isTenantEntity, private RequestCollection $requestCollection, bool $isApiRecord, bool $isAbstract, bool $noRef, private ?string $path=null, private array $responseBody=[])
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    public function isTenantEntity(): bool
    {
        return $this->isTenantEntity;
    }

    public function addProperty(PropertyInterface $property): self
    {
        $this->properties[] = $property;
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRequestCollection(): RequestCollection
    {
        return $this->requestCollection;
    }

    public function getResponse(Ulid $id, SchemaFixtureService $schemaFixtureService):Response
    {
        return new Response($id, $schemaFixtureService, $this);
    }

    public function getPath(mixed $id=null): string
    {
        return sprintf('/%ss%s', $this->tableName, $id?'/'.$id:'');
    }

    public function debug(bool $deep=false): mixed
    {
        return [
            'class'=>$this->class,
            'basePath'=>$this->getPath(),
            'tableName'=>$this->tableName,
            'isTenantEntity'=>$this->isTenantEntity,
            'properties'=> $deep
                ?array_map(function($property){return $property->debug();}, $this->properties)
                :'property count: '.count($this->properties)
        ];
    }
}