<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use App\Test\Service\RandomRecordService;
use ArrayObject;
use Symfony\Component\Uid\Ulid;
use App\Test\Service\SchemaFixtureService;

final class IriReferenceProperty implements PropertyInterface
{
    public function __construct(private string $name, private string $path, private Request $request, private ?string $ref=null)
    {
        if(!$path) {
            throw new \Exception(sprintf('Missing path for %s::%s', $request->getClass(), $name));
        }
    }

    public static function create(string $propertyName, array $openApiSchema, Request $request, ?string $ref=null):self
    {
        if(!isset($openApiSchema[$propertyName])) {
            return new IriReferenceProperty($propertyName, 'MISSING', $request, $ref);
        }
        if(!$openApiSchema[$propertyName]->offsetExists('example')) {
            throw new \Exception(sprintf('No example: %s %s %s', $propertyName, $request->getClass(), json_encode($openApiSchema[$propertyName])));
        }
        return new IriReferenceProperty($propertyName, explode('/', ltrim($openApiSchema[$propertyName]->offsetGet('example'), '/'))[0], $request, $ref);
    }

    public function getName():string
    {
        return $this->name;
    }

    public function getValue(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed
    {
        return sprintf('/%s/%s', $this->path, $this->getRecord($id, $schemaFixtureService));
    }

    private function getRecord($id, $schemaFixtureService): mixed
    {
        return $this->isTenantEntity()?$this->getTenantRecord($id, $schemaFixtureService):$this->getNonTenantRecord($id, $schemaFixtureService);
    }
    private function isTenantEntity(): bool
    {
        return $this->request->getRequestCollection()->getRequestByPath($this->path)->isTenantEntity();
    }
    private function getTenantRecord(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed
    {
        if(!$obj = $schemaFixtureService->getRandomRecordService()->getTenantRecord($id, $this->getAssociatedRequest()->getClass())) {
            throw new InvalidPropertyException(sprintf('Could not get record for tenant (%s) entity %s', $id->toRfc4122(), $this->request->getClass()));
        }
        return $obj->getId();
    }
    private function getNonTenantRecord(Ulid $id, SchemaFixtureService $schemaFixtureService): mixed
    {
        if(!$obj = $schemaFixtureService->getRandomRecordService()->getNonTenantRecord($this->getAssociatedRequest()->getClass())) {
            throw new InvalidPropertyException(sprintf('Could not get record for non-tenant (%s) entity %s'.PHP_EOL, $id->toRfc4122(), $this->request->getClass()));
        }
        return method_exists($obj, 'getIdentifier')?$obj->getIdentifier():$obj->getId();
    }

    private function getAssociatedRequest(): Request
    {
        return $this->request->getRequestCollection()->getRequestByPath($this->path);
    }

    public function debug(bool $deep=false): mixed
    {
        return [
            'name'=>$this->name,
            'path'=>$this->path,
            'request'=>$deep?$this->request->debug():$this->request->getClass()
        ];
    }
}