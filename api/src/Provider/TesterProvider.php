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

namespace App\Provider;

use Repository\Acl\Asset\AssetRepository;
use App\Entity\Asset\Asset;
use App\Entity\User\SystemUser;

use App\Entity\Acl\AbstractResourceAcl;
use App\Entity\Acl\AbstractDocumentAcl;

use Doctrine\DBAL\Types\Type;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use App\Entity\Document\MediaType;
use App\Service\AttributeBeautifier;
use App\Test\Service\SchemaBuilderService;
use App\DataFixtures\AppFixtures;
use App\Traits\HelperTrait;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;
use Symfony\Component\Uid\Uuid;
use App\Entity\Organization\Tenant;

//use App\Entity\Test\TestTenant;
//use App\Entity\Test\TestVendor;
//use App\Entity\Test\TestTenantUser;
//use App\Entity\Test\TestVendorUser;

//use App\Entity\Test2\Test2Tenant as TestTenant;
//use App\Entity\Test2\Test2Vendor as TestVendor;
//use App\Entity\Test2\Test2TenantUser as TestTenantUser;
//use App\Entity\Test2\Test2VendorUser as TestVendorUser;

use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;

use App\Tests\ResourceAclTest;

use App\Entity\Test3\Test3Tenant as TestTenant;
use App\Entity\Test3\Test3Vendor as TestVendor;
use App\Entity\Test3\Test3TenantUser as TestTenantUser;
use App\Entity\Test3\Test3VendorUser as TestVendorUser;
use App\Entity\Acl\HasResourceAclInterface;

use App\Security\Service\AccessRoleCreatorService;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
class TesterProvider implements ProviderInterface
{
    use HelperTrait;
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private SchemaBuilderService $schemaBuilderService,
        private AppFixtures $appFixtures,
        private RequestStack $requestStack,
        private AttributeBeautifier $attributeBeautifier,
        private AccessRoleCreatorService $accessRoleCreatorService,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
    {
        switch($operation->getUriTemplate()) {
            case '/test/accounts':
                $testingTenant = $this->getTestingTenant();
                $users = array_merge(
                    $testingTenant->getUsers()->toArray(),
                    $this->getTestingSystemUsers(),
                );
                foreach($testingTenant->getVendors() as $vendor) {
                    $users=array_merge($users, $vendor->getUsers()->toArray());
                }
                foreach($users as $user) {
                    $id = $user->getId()->toRfc4122();
                    if(!isset($logons[$id])) {
                        $logons[$id] = array_merge($user->getLogon('testing'), ['roles'=>$user->getRoles()]);
                    }
                }
                return array_values($logons);
            case '/test/sql-json':
                // Handled by DirtyJsonEncoder.  Fix when I get time.
                print_r($context);exit;
                return ['complete'=>true];
            case '/test/uid':
                $uid = $context['filters']['uid'];
                $uuid = Uuid::fromString($uid);
                $ulid = Ulid::fromString($uid);

                printf('%-20s %s'.PHP_EOL.PHP_EOL, 'Provided', $uid);

                printf('%-20s %-50s %s'.PHP_EOL, 'Format', 'UUID', 'ULID');
                printf('%-20s %-50s %s'.PHP_EOL, 'toBinary()', bin2hex($uuid->toBinary()), bin2hex($ulid->toBinary()));
                printf('%-20s %-50s %s'.PHP_EOL, 'toBase32()', $uuid->toBase32(), $ulid->toBase32());
                printf('%-20s %-50s %s'.PHP_EOL, 'toBase58()', $uuid->toBase58(), $ulid->toBase58());
                printf('%-20s %-50s %s'.PHP_EOL, 'toRfc4122()', $uuid->toRfc4122(), $ulid->toRfc4122());
                printf('%-20s %-50s %s'.PHP_EOL, '(string)', (string) $uuid, (string) $ulid);
                //printf(PHP_EOL.'%-20s %-50s'.PHP_EOL, 'postgresql', $this->uidToPostgreSql($ulid));
                exit;
            case '/test/id_getter':
                $tenantId = Ulid::fromString($context['filters']['tenantId']);

                $fqcn = '\\'.trim($context['filters']['fqcn'], '\\');
                if(!class_exists($fqcn)) {
                    throw new \Exception('Class does not exist: '.$fqcn);
                }
                if(!$this->entityManager->getMetadataFactory()->isTransient($fqcn)) {
                    throw new \Exception('Class is not an entity: '.$fqcn);
                }
                $primaryKeys = $this->entityManager->getClassMetadata($fqcn)->getIdentifierFieldNames();

                $fields = array_map(function($pk){return 'o.'.$pk;}, $primaryKeys);

                $query = $this->entityManager->createQueryBuilder($fqcn)
                ->select($fields)
                ->from($fqcn, 'o')
                ->join('o.tenant', 't')
                ->where('t.id = :id')
                ->setParameter('id', $tenantId, 'ulid')
                ->getQuery();

                echo($this->showDoctrineQuery($query).PHP_EOL.PHP_EOL);
                echo(ltrim($fqcn, '\\').PHP_EOL.PHP_EOL);
                $arr=[];
                foreach($query->getResult() as $rs) {
                    $arr[] = implode(' | ', array_map(function(string $v){return (string)$v;}, $rs));
                }
                $hn=[];
                $hv=[];
                foreach($rs??[] as $n=>$dummy) {
                    $hn[] = $n;
                    $hv[] = '%-'.(2+strlen((string)$n).'s');
                }
                printf(implode(' | ', $hv).PHP_EOL, ...$hn);
                exit(implode(PHP_EOL, $arr));
            case '/test/clear-cache':
                exec('php '.dirname(dirname(__DIR__)).'/bin/console cache:clear');
                return ['complete'=>true];
            case '/test/get-class-file-location':
                (new Response($this->getClassFile($this->sanitizeClass($this->getQueryProperty('class'))), Response::HTTP_OK, ['content-type' => 'text/plain']))->send();
                break;
            case '/test/get-class-file':
                return (new BinaryFileResponse($this->getClassFile($this->sanitizeClass($this->getQueryProperty('class')))))->send();
            case '/test/beautify-attributes':
                (new Response($this->attributeBeautifier->parseFile($this->getClassFile($this->sanitizeClass($this->getQueryProperty('class')))), Response::HTTP_OK, ['content-type' => 'text/plain']))->send();
                break;
            case '/test/custom':
                return $this->testSomething();
            case '/test/schema/request_collection':
                return $this->schemaBuilderService->debugRequestCollection($this->getQueryProperty('filter'));
            case '/test/schema/open_api':
                return $this->schemaBuilderService->getOpenApi($this->getQueryProperty('filter'));
            case '/test/schema/all':
                return $this->schemaBuilderService->getAllSchemas($this->getQueryProperty('filter'));
            case '/test/test':
                chdir(dirname(dirname(__DIR__)));
                $rs=shell_exec('bin/phpunit tests/AppTest.php');
                exit($rs);
            case '/test/permissions':
                echo('DEFAULT TENANT ACL PERMISSION SETTINGS'.PHP_EOL);
                foreach($this->entityManager->getRepository(Tenant::class)->findAll() as $tenant) {
                    echo(str_pad(sprintf('%s (%s)','ResourceAclPermissionSetPrototype', $tenant->getId()), 70).$tenant->getResourceAclPermissionSetPrototype()->getCrudString().PHP_EOL);
                    echo(str_pad(sprintf('%s (%s)','DocumentAclPermissionSetPrototype', $tenant->getId()), 70).$tenant->getDocumentAclPermissionSetPrototype()->getCrudString().PHP_EOL);
                }
                echo(PHP_EOL.PHP_EOL.'RESOURCE ACL PERMISSION SETTINGS'.PHP_EOL);
                $this->displayPermissions(AbstractResourceAcl::class);
                echo(PHP_EOL.PHP_EOL.'DOCUMENT ACL PERMISSION SETTINGS'.PHP_EOL);
                $this->displayPermissions(AbstractDocumentAcl::class);
                exit;
            case '/test/resource_acl_classes':
                exit(implode(PHP_EOL, array_filter(get_declared_classes(), function(string $class){
                    return is_subclass_of($class, HasResourceAclInterface::class);
                })));
            default: throw new \Exception('Unknown path: '.$operation->getUriTemplate());
        }
    }

    private function displayPermissions(string $aclClass):void
    {
        foreach($this->entityManager->getRepository($aclClass)->findAll() as $acl) {
            echo(PHP_EOL.str_pad(sprintf('%s (%s)', (new \ReflectionClass($acl->getResource()))->getShortName(), $acl->getResource()->getId()), 70).$acl->getPermissionSet()->getCrudString().PHP_EOL);
            foreach($acl->getMembers() as $member) {
                echo(str_pad(sprintf('- member %s (%s)', (new \ReflectionClass($member->getUser()))->getShortName(), $member->getUser()->getId()), 70).$member->getPermission()->getCrudString().PHP_EOL);
            }
        }
    }

    private function uidToPostgreSql($uid):string
    {
        $uid = str('-', $uid->toRfc4122());
        array_splice($uid, 3, 1, $uid[2].$uid[3]);
        return implode('-', [$uid[0].$uid[1], $uid[2].$uid[3], $uid[4].$uid[5]]);
    }

    private function getClassFile(string $classname):string
    {
        return (new \ReflectionClass($classname))->getFileName();
    }
    private function getQueryProperty(string $name):?string
    {
        return $this->requestStack->getCurrentRequest()->query->get($name);
    }
    private function sanitizeClass(string $classname):string
    {
        $classname = ltrim(rtrim($classname, ';'), 'use ');
        return str_replace('\\\\', '\\', '\\'.$classname);
    }

    private function getTestingTenant()
    {
        return $this->entityManager->getRepository(Tenant::class)->find(Ulid::fromRfc4122('11111111-1111-1111-1111-111111111111'));
    }
    private function getTestingSystemUsers()
    {
        return $this->entityManager->getRepository(SystemUser::class)->findBy(['firstName' => '_TESTER_']);
    }

    // ##############################################

    private function testSomething()
    {
        $max = AclPermission::create('VENDOR','VENDOR','ALL','VENDOR')->getValue();
        for ($v = 0; $v <= $max; $v++) {
            try{
                $permission = AclPermission::createFromValue($v);
            }
            catch(\Exception $e) {
                exit('xxx');
            }
            echo($permission->toCrudString().' '.$v.PHP_EOL);
        }
        exit('value: '.$x->getValue());
        $resource = $this->entityManager->getRepository(Asset::class)->findAll()[2];
        $rs = $this->serialize($resource);
        //dump($rs);
        print_r($rs);
        exit(gettype($rs));
        echo($resource->getName().PHP_EOL);

        $resourceId = $resource->getId();

        $noAccess = array_fill_keys(['read', 'update'], 'ALL');
        $resourceAcl = $resource->getResourceAcl();

        $permissionSet1 = $resourceAcl->getPermissionSet();
        echo($permissionSet1->getCrudString().PHP_EOL);

        $permissionSet = AclPermissionSet::createFromArray($noAccess, $noAccess, $noAccess, $noAccess);
        echo($permissionSet->getCrudString().PHP_EOL);

        $resourceAcl->setPermissionSet($permissionSet);

        $this->entityManager->persist($resourceAcl);
        $this->entityManager->flush();


        exit;
        //print_r($resource->debug());
        //print_r($resourceAcl->debug());
        //$userClient->saveEntity($resourceAcl);
        $userClient->saveEntity($resource);

        exit;

        exit('count: '.count($asset));
        $test = new ResourceAclTest();
        $test->test2();
        exit;
        foreach($test->getEntityPermissions() as $key=>$value)
        {
            $test->testAclPermissions(...$value);
        }

        exit(get_class($test));

        print_r($this->accessRoleCreatorService->createDefaultRoleYaml('app.default.roles'));
        exit;
        $obj = new \App\Entity\TestObject;
        echo(get_class($obj).PHP_EOL);
        $this->entityManager->persist($obj);
        exit('testSomething');
        $user = $this->getTestingTenant->getUsers()[0];
        //new UploadedFile(string $path, string $originalName, string $mimeType = null, int $error = null, bool $test = false)

        //$file = new UploadedFile(__DIR__.'/test.txt', 'test.txt');
        $file = new UploadedMockFile(__DIR__.'/test.txt', 'test.txt');
        //$file = fopen(__DIR__.'/test.txt', 'r');
        //$response = $this->request($user, 'POST', '/media', [], ['Content-Type' => 'multipart/form-data'], ['files' => ['file' => $file]]);
        $response = $this->sendFile($user, '/media', 'text/plain', 100);
        echo(PHP_EOL.get_class($response).PHP_EOL);
        //print_r(get_class_methods($response));
        //print_r($response->getInfo());
        //print_r($response->toArray());
        exit;
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(MediaObject::class);
        $this->assertJsonContains([
            'title' => 'My file uploaded',
        ]);
    }
    private function serialize(object $entity, bool $excludeUriTemplate=true):array
    {
        $reflection = new \ReflectionClass(get_class($entity));
        $attributes = $reflection->getAttributes();

        $contexts = [];
        foreach ($attributes as $attribute) {
            if($attribute->getName() === 'ApiPlatform\Metadata\ApiResource') {
                $arguments = $attribute->getArguments();
                if(isset($arguments['uriVariables']) || ($excludeUriTemplate && isset($arguments['uriTemplate']))) {
                    continue;
                }
                foreach ($arguments['operations']??[] as $operation){
                    if(get_class($operation)==='ApiPlatform\Metadata\Get') {
                        $getcontext = $operation->getNormalizationContext();
                        break;
                    }
                }
                $contexts[] = array_merge_recursive($arguments['normalizationContext']??[], $getcontext??[]);
            }
        }
        $context = [];
        for (end($contexts); key($contexts)!==null; prev($contexts)){
            $context = array_merge_recursive($context, current($contexts));
        }

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return sprintf('<<RECURSION: %s (%s)>>', get_class($object), $object->getId());
            },
        ];
        if($context['enable_max_depth']??false) {
            $defaultContext[] = [AbstractObjectNormalizer::MAX_DEPTH_HANDLER => function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                return sprintf('<<MAX_DEPTH: %s (%s)>>', get_class($innerObject), $innerObject->getId());
            }];
            $defaultContext[] = [AbstractObjectNormalizer::CIRCULAR_REFERENCE_LIMIT => 1];
        }

        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())), null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer]);
        return $serializer->normalize($entity, null, $context);
    }

    private function sendFile(string $token, string $path, string $mediaType, int $size, string $accept='application/json', array $extra=[])//:TraceableResponse
    {
        $fileStream = fopen(__DIR__.'/testfile.txt', 'r');
        $headers = [
            'Accept' => $accept,
            'Content-Type' => 'multipart/form-data',
            'authorization' => 'Bearer '.$token,
        ];
        print_r($headers);
        return $this->httpClient->request('POST', 'https://facdocs.zadaba.com'.$path, ['body' => ['file'=>$fileStream],'headers' => $headers]);
        return $this->httpClient->request('POST', 'https://facdocs.zadaba.com'.$path, array_merge(['body' => ['file'=>$fileStream],'headers' => $headers], $extra),);
    }

    private function sendFilexx(UserInterface $user, string $path, string $mediaType, int $size, string $accept='application/json', array $extra=[])//:Response
    {
        return $this->helper->sendFile($this->getToken($user), $path, $mediaType, $size, $accept, $extra);
    }

    private function testSomethinxxxxg()
    {
        exit('testSomething');
        $rootUser = $this->getRootUser();

        $tenant = new TestTenant;
        $vendor = new TestVendor;
        $tenant->addVendor($vendor);
        // Technically only need to persist tenant since others will cascade persist, but just to be double sure.
        $this->entityManager->persist($tenant);
        $this->entityManager->persist($vendor);
        $this->entityManager->flush();
        $tenantUser = new TestTenantUser;
        $vendorUser = new TestVendorUser;
        $vendorUser2 = new TestVendorUser;
        $tenant->addUser($tenantUser);
        $vendor->addUser($vendorUser);
        $vendor->addUser($vendorUser2);

        // Simulating being set by blamable listner.
        $tenant->setCreateBy($rootUser);//->setUpdateBy($rootUser);
        $vendor->setCreateBy($rootUser);//->setUpdateBy($rootUser);
        $tenantUser->setCreateBy($rootUser);//->setUpdateBy($rootUser);
        $vendorUser->setCreateBy($rootUser);//->setUpdateBy($rootUser);
        $vendorUser2->setCreateBy($rootUser);//->setUpdateBy($rootUser);


        $this->entityManager->persist($tenantUser);
        $this->entityManager->persist($vendorUser);
        $this->entityManager->persist($vendorUser2);
        /*
        printf('%-30s with ID %s has organization %s'.PHP_EOL, get_class($tenantUser), $this->getId($tenantUser), $this->getId($tenantUser->getOrganization()));
        printf('%-30s with ID %s has organization %s'.PHP_EOL, get_class($vendorUser), $this->getId($vendorUser), $this->getId($vendorUser->getOrganization()));
        printf('%-30s with ID %s has organization %s'.PHP_EOL, get_class($vendorUser2), $this->getId($vendorUser2), $this->getId($vendorUser2->getOrganization()));
        printf('%-30s with ID %s has createById %s'.PHP_EOL, get_class($tenantUser), $this->getId($tenantUser), $this->getId($tenantUser->getCreateBy()));
        printf('%-30s with ID %s has createById %s'.PHP_EOL, get_class($vendorUser), $this->getId($vendorUser), $this->getId($vendorUser->getCreateBy()));
        printf('%-30s with ID %s has createById %s'.PHP_EOL, get_class($vendorUser2), $this->getId($vendorUser2), $this->getId($vendorUser2->getCreateBy()));
        */
        try {
            $this->entityManager->flush();
        }
        catch(\Exception $e) {
            exit($e->getMessage());
        }
    }
    private function getId($obj):string
    {
        return ($id=$obj->getId())?$id->toRfc4122():'NULL';
    }
    private function getRootUser():TestTenantUser
    {
        if(!$rootUser = $this->entityManager->getRepository(TestTenantUser::class)->find(new NilUlid)) {
            exit('create root user'.PHP_EOL);
            $conn = $this->entityManager->getConnection();
            #$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test2_abstract_organization', 'create_by_id'));
            #$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test2_abstract_organization', 'update_by_id'));
            $conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test3_tenant', 'create_by_id'));
            //$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test3_tenant', 'update_by_id'));
            $conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test3_abstract_user', 'create_by_id'));
            //$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s DROP NOT NULL', 'test3_abstract_user', 'update_by_id'));
            $rootTenant = new TestTenant(new NilUlid);
            $rootUser = new TestTenantUser(new NilUlid);
            $rootTenant->addUser($rootUser);
            $this->entityManager->persist($rootTenant);
            $this->entityManager->flush();
            $rootTenant->setCreateBy($rootUser);//->setUpdateBy($rootUser);
            $rootUser->setCreateBy($rootUser);//->setUpdateBy($rootUser);
            $this->entityManager->persist($rootTenant);
            $this->entityManager->flush();
            $conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', 'test2_abstract_organization', 'create_by_id'));
            //$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', 'test2_abstract_organization', 'update_by_id'));
            $conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', 'test3_tenant', 'create_by_id'));
            //$conn->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s SET NOT NULL', 'test3_tenant', 'update_by_id'));
        }
        return $rootUser;
    }

    private function deleteRoot():void
    {
        $this->entityManager->remove($rootUser->getOrganization());
        $this->entityManager->flush();
    }

    private function getExisting(): array
    {
        $entities = [];
        $conn = $this->entityManager->getConnection();
        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $classMetadata) {
            $tableName = $classMetadata->getTableName();
            if(in_array($tableName, ['abstract_resource_member', 'money'])) {
                printf('Skip %s %s'.PHP_EOL, $tableName, $classMetadata->getName());
            }
            else {
                $count = $conn->query('SELECT COUNT(*) FROM '.$tableName)->fetchOne();
                $entities[] = sprintf('%s %s %s', $count, $tableName, $classMetadata->getName());
            }
        }
        print_r($entities);
        foreach(['base_user','system_user','base_organization','system_organization'] as $tableName) {
            printf('%s'.PHP_EOL, $tableName);
            print_r($conn->query('SELECT * FROM '.$tableName)->fetchAll());
        }

        exit;
        return $entities;
    }

    private function deleteStuff()
    {
        echo('testSomething'.PHP_EOL);
        $this->setAll(Tenant::class, ['createBy', 'updateBy'], new NilUlid);
        //$this->setAll(Tenant::class, ['rootAsset'], null);
        //$this->manager->flush();

        echo('deleteAll'.PHP_EOL);
        $this->deleteAll(Tenant::class);

        echo('return'.PHP_EOL);
    }

    private function oldstuff()
    {
        print_r($this->schemaBuilderService->getOpenApi());
        return ['fixme'=>true];
        print_r(get_class_methods($operation));
        print_r($context);

        exit;
        exit(get_class($x));
        $this->getTypesMap();
        $this->logger->info('testing');
        if(isset($uriVariables['fake'])) {
            \ASSETREPOSITORY->debugAssetLevels(new Asset);
        }
        exit('hello');
        print_r(Type::getTypesMap());
        /*
        Shows normal 25 (which includes [guid] => Doctrine\DBAL\Types\GuidType) plus the following:
        [
        [phone_number] => App\Doctrine\Types\PhoneNumber
        [PhoneNumber] => App\Doctrine\Types\PhoneNumber
        [uuid] => Symfony\Bridge\Doctrine\Types\UuidType
        [ulid] => Symfony\Bridge\Doctrine\Types\UlidType
        ]
        */
    }

    private function deleteAll(string $class):self
    {
        $this->entityManager->createQuery(sprintf('DELETE FROM %s', $class))->execute();
        return $this;
    }

    private function setAll(string $class, array $properties, $value):self
    {
        $query = $this->entityManager->createQueryBuilder()->update($class, 'o');
        foreach($properties as $property) {
            $query->set('o.'.$property, ':'.$property);
            $query->setParameter($property, $value, $value instanceof Ulid?'ulid':null);
        }
        printf('setAll %s $properties %s value: %s'.PHP_EOL, $class, implode(',', $properties), gettype($value));
        $query->getQuery()->execute();
        return $this;
    }
}
