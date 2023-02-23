<?php

/*
* This file is part of the FacDocs project.
*
* (c) Michael Reed villascape@gmail.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace App\Test\Service;

use ApiPlatform\Symfony\Bundle\Test\Response;
use ApiPlatform\Symfony\Bundle\Test\Client;

//use ApiPlatform\Serializer\ItemNormalizer;
//use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Service\ManualSerializerService;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Ulid;

use Doctrine\ORM\EntityManagerInterface;

use App\Test\Model\Api\MessageLogItem;
use App\Test\Model\Api\EntityTracker;
use App\Test\Model\Api\ResponseInterface;
use App\Entity\User\UserInterface;
use App\Entity\Organization\Tenant;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Security\Service\AccessRoleService;

class ApiRequestService
{
    public function __construct(private SchemaFixtureService $schemaFixtureService, private TestHelperService $testHelperService, private ManualSerializerService $serializer, private AccessRoleService $accessRoleService, private EntityManagerInterface $entityManager, private EntityPersisterService $entityPersisterService, private bool $debug)
    {
    }

    public function normalize(object $obj):array
    {
        return $this->serializer->normalize($obj);
    }
    public function getSerializer():ManualSerializerService
    {
        return $this->serializer;
    }

    public function getBody(string $class, ?Tenant $tenant):array
    {
        // $id not used for Tenant
        return $this->schemaFixtureService->getRequestBody($tenant?$tenant->getId():new Ulid, $class);
    }

    public function getPath(string $class, mixed $id = null):string
    {
        return $this->schemaFixtureService->getPath($class, $id);
    }
    public function getClass(string $path):string
    {
        return $this->schemaFixtureService->getClass($path);
    }
    public function getIdentifier(string $class):array
    {
        return $this->schemaFixtureService->getIdentifier($class);
    }
    public function getIdsFromPath(string $path):array
    {
        $ids = [];
        foreach(explode('/', ltrim($path, '/')) as $part) {
            if(Ulid::isValid($part)) {
                $ids[] = $part;
            }
        }
        return $ids;
    }

    public function createLinkFromResponse(ResponseInterface|Response $response):string
    {
        return $response->toArray()['@id'];
    }

    public function createLink(HasUlidInterface $obj, bool $item=true, bool $random=false):string
    {
        return $this->getPath(get_class($obj), $item?$this->getObjectId($obj, $random):null);
    }
    private function getObjectId(HasUlidInterface $obj, bool $random=true):string
    {
        return $obj->getId()->{['toBase32', 'toBase58', 'toRfc4122'][$random?rand(0,2):0]}();
    }

    public function addMessageLogItem(string $message): void
    {
        $this->logger->addLogItem(new MessageLogItem($message));
    }

    public function createEntityTracker(object $entity, array $serializerContext=[], array $serializerOptions=[]):EntityTracker
    {
        return new EntityTracker($entity, $this->serializer, $serializerContext, $serializerOptions);
    }

    // Future.  Change to get from serializer instead of DB?
    public function getEntityFromIdClass(string $class, Ulid $id):mixed
    {
        return $this->testHelperService->getUlidRecordById($class, $id);
    }
    public function getEntity(ResponseInterface $entityResponse):mixed
    {
        return ($class = $this->apiRequestService->getClass($path)) && ($id = $this->apiRequestService->getIdsFromPath($path))
        ?$this->testHelperService->getUlidRecordById($entityResponse->getClass(), $entityResponse->getId())
        :null;
    }

    public function createTenantEntity(Tenant $tenant, string $class, array $body=[], array $removedProperties=[], bool $populate=true):BelongsToTenantInterface
    {
        if (!is_subclass_of($class, BelongsToTenantInterface::class)) {
            throw new \Exception(sprintf('%s is not an instance of %s', $class, BelongsToTenantInterface::class));
        }
        if($populate) {
            $body = array_merge($this->getBody($tenant, $class), $body);
        }
        if($removedProperties) {
            $body = array_diff_key($body, array_flip($removedProperties));
        }
        $entity = $this->denormalizer->denormalize($body, $class); //, null, ['groups' => 'asset:write']);
        $entity->setTenant($tenant);
        return $entity;
        // Temp hack solution until I better understand serializer.
        $entity = new $class();
        foreach($body as $property=>$value) {
            $method = sprintf('set%s', ucfirst($property));
            if(!method_exists($entity, $method)) {
                continue;
            }
            $entity->$method($value);
        }
        return $entity;
    }

    public function saveEntity(BelongsToTenantInterface $entity, ?Tenant $tenant=null, ?UserInterface $user=null, bool $flush=true): self
    {
        $this->entityPersisterService->saveEntity($entity, $tenant, $user, $flush);
        return $this;
    }

    public function getSchemaFixtureService():SchemaFixtureService
    {
        return $this->schemaFixtureService;
    }
    public function getTestHelperService():TestHelperService
    {
        return $this->testHelperService;
    }
    public function getEntityPersisterService():EntityPersisterService
    {
        return $this->entityPersisterService;
    }
    public function getEntityManager():EntityManagerInterface
    {
        return $this->entityManager;
    }
    public function getAccessRoleService(): AccessRoleService
    {
        return $this->accessRoleService;
    }
}
