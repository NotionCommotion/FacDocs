<?php

declare(strict_types=1);

namespace App\Test\Service;

use Symfony\Component\Uid\Ulid;
use Faker\Generator as FakerGenerator;
use App\DataFixtures\Provider\TestHelperProvider;
use App\Entity\Organization\Tenant;

use App\Test\Model\Schema\RequestCollection;
use App\Test\Model\Schema\Request;
use App\Test\Model\Schema\Response;
use App\Test\Model\Schema\PropertyInterface;
use App\Service\DebugTesterService;
use ArrayObject;

final class SchemaFixtureService
{
    private RequestCollection $requestCollection;
    private array $excludedPropertyLinks;

    public function __construct(
        SchemaBuilderService $schemaBuilderService,
        private FakerGenerator $fakerGenerator,
        private RandomRecordService $randomRecordService,
    )
    {
        $this->requestCollection = $schemaBuilderService->fetchRequestCollection(false, true);
        $this->fakerGenerator->addProvider(new TestHelperProvider($fakerGenerator));
        $this->excludedPropertyLinks = $schemaBuilderService->getExcludedPropertyLinks();
    }

    public function isExcludedProperty(Request $request, PropertyInterface $property)
    {
        return ($arr = $this->excludedPropertyLinks[$request->getClass()]??null)?in_array($property->getName(), $arr):false;
    }

    public function getFakerGenerator():FakerGenerator
    {
        return $this->fakerGenerator;
    }
    public function getRandomRecordService():RandomRecordService
    {
        return $this->randomRecordService;
    }

    public function getAll(Ulid $id):array
    {
        return $this->requestCollection->getResponse($id, $this);
    }

 
    public function getRequestBody(Ulid $id, string $class):array
    {
        return $this->requestCollection->getRequest($class)->getResponse($id, $this)->toArray();
    }
    public function getPath(string $class, mixed $id=null):string
    {
        return $this->requestCollection->getRequest($class)->getPath($id);
    }
    public function getClass(string $path):string
    {
        return $this->requestCollection->getRequestByPath($path)->getClass();
    }
    public function getIdentifier(string $class):array
    {
        return $this->requestCollection->getRequest($class)->getIdentifier();
    }

    public function getRequestBodyResponse(Ulid $id, string $class):Response
    {
        return $this->requestCollection->getRequest($class)->getResponse($id, $this);
    }

    // $id doesn't do anything.
    public function getTenantResponse():Response
    {
        return $this->requestCollection->getRequest(Tenant::class)->getResponse(new Ulid, $this);
    }

    // Just for help determining which ones I want to allow them to be overriden when attempting to update.
    public function getAllEntitiesWithProperties():array
    {
        return $this->requestCollection->getAllEntitiesWithProperties();
    }
}
