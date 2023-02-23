<?php

/*
* This file is part of the FacDocs project.
*
* (c) Michael Reed villascape@gmail.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace App\Test\Model;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Symfony\Bundle\Test\Response;
use ApiPlatform\Metadata\Get;
//use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use App\Test\Service\ApiRequestService;
use App\Test\Service\TestHelperService;
use App\Test\Service\SchemaFixtureService;
use App\Entity\User\UserInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\Vendor;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\Acl\HasAclInterface;
use App\Entity\Acl\AclInterface;
use App\Entity\Acl\AclMemberInterface;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\PhpUnitTest\PhpUnitTest;
use App\Entity\PhpUnitTest\PhpUnitTestRecord;
use App\Exception\InvalidAclPermissionException;
use App\Test\Model\Api\EntityTracker;
use App\Test\Model\Api\ResourcePermissionChecker;
use App\Test\Model\Api\EntityResponse;
use App\Test\Model\Api\ApiUser;
use App\Test\Model\Api\ApiClient;
use App\Test\Service\TestLoggerService;
use App\Test\Model\Api\LogFormatter\LogFormatterInterface;
use App\Test\Model\Api\LogFormatter\DefaultLogFormatter;
use App\Test\Model\Api\LogFormatter\VerboseLogFormatter;
use App\Test\Model\Api\LogFormatter\MinimumLogFormatter;
use App\Test\Model\Api\LogFormatter\HtmlLogFormatter;
use App\Test\Model\Api\LogFormatter\HtmlTableLogFormatter;
use Throwable;

abstract class AbstractTestCase extends ApiTestCase
{
    //use RefreshDatabaseTrait;

    protected const API_CLIENT_OPTIONS = ['echoLog'=>false, 'debug'=>false];
    protected const DEBUG_ECHO = false;
    protected const ERROR_UPON_DUPLICATE_ACCEPTANCE = true;

    private const PASSWORD = 'testing';


    private static bool $verbose = false;
    private static TestLoggerService $testLoggerService;
    protected ApiRequestService $apiRequestService;
    private ApiClient $apiClient;
    private static PhpUnitTest $phpUnitTest;

    abstract protected static function getLogOutputFile():string;
    abstract protected static function getDbTestName():string;
    protected static function getDbTestDescription():?string
    {
        return null;
    }

    protected static function getLogFormatter()
    {
        return new HtmlTableLogFormatter(static::getLogOutputFile());
    }

    // Declared in SystemUserTrait and/or ApiRequestServiceTrait
    public static function setUpBeforeClass(): void
    {
        // Kludge.  See https://stackoverflow.com/questions/74788815/how-to-use-autowired-services-when-using-phpunit
        //static::getContainer()->set('app.test.api.request.service', $apiRequestService);
        //self::$apiRequestService = $apiRequestService;
        self::$testLoggerService = static::getContainer()->get(TestLoggerService::class)->setTotalLogCount(static::getTotalLogCount());
        self::$phpUnitTest = (new PhpUnitTest)->setName(static::getDbTestName())->setDescription(static::getDbTestDescription());
    }
    protected static function getTotalLogCount(): ?int
    {
        return null;
    }
    protected function addToTotalLogCount(int $count): self
    {
        self::$testLoggerService->addToTotalLogCount($count);
        return $this;
    }

    public static function tearDownAfterClass(): void
    {
        //static::getLogFormatter()->process(self::$testLoggerService);
        $db = static::getContainer()->get(EntityManagerInterface::class);
        $phpUnitTest = self::$phpUnitTest;
        $phpUnitTest->setEndAt(new \DateTimeImmutable);
        $db->persist($phpUnitTest);
        foreach(self::$testLoggerService as $logItem) {
            $phpUnitTestRecord = $logItem->createPhpUnitTestRecord();
            $phpUnitTest->addPhpUnitTestRecord($phpUnitTestRecord);
            $db->persist($phpUnitTestRecord);
        }
        $db->flush();
    }
    protected function setUp(): void
    {
        $this->apiRequestService = static::getContainer()->get(ApiRequestService::class);
        $this->apiClient = new ApiClient(static::createClient(), self::$testLoggerService, $this->apiRequestService, $this, static::API_CLIENT_OPTIONS);
    }

    // Just for help determining which ones I want to allow them to be overriden when attempting to update.
    protected function getAllClassProperties(): array
    {
        $arr = [];
        foreach($this->apiRequestService->getSchemaFixtureService()->getAllEntitiesWithProperties() as $class=>$props) {
            foreach($props as $prop) {
                $arr[] = $prop;
            }
        }
        return array_values(array_unique($arr));
    }

    //protected function assertPreConditions(): void{}
    //protected function assertPostConditions(): void{}
    //protected function tearDown(): void{}
    //protected function onNotSuccessfulTest(Throwable $t): void{}

    ####  CLIENT METHODS ####
    // Not used.
    public function authenticateClient(UserInterface $user): self
    {
        $this->apiClient->setDefaultOptions($this->getCredentials($user));
        return $this;
    }
    protected function createClientWithCredentials(UserInterface $user): Client
    {
        return static::createClient([], $this->getCredentials($user));
    }
    private function getCredentials(UserInterface $user): array
    {
        return ['headers' => ['authorization' => 'Bearer '.$this->getToken($user)]];
    }
    protected function authenticate(array $credentials, ?Client $client=null): Response
    {
        return $client??static::createClient()->request('POST', '/authentication_token', ['headers' => ['Content-Type' => 'application/json'],'json' => $credentials]);
    }
    protected function getToken(UserInterface $user, Client $client): string
    {
        return json_decode($this->authenticate($this->getLogon($user), $client)->getContent())->token;
    }
    protected function getLogon(UserInterface $user): array
    {
        return $user->getLogon($this->getPassword());
    }

    protected function createResourcePermissionChecker(UserInterface $user, HasAclInterface $entity)
    {
        return new ResourcePermissionChecker($user, $entity, $this->apiRequestService->getAccessRoleService(), self::ERROR_UPON_DUPLICATE_ACCEPTANCE);
    }

    protected function getSystemApiUser(string $password=self::PASSWORD): ApiUser
    {
        return $this->createApiUser(static::getContainer()->get(ApiRequestService::class)->getTestHelperService()->getSystemUser(), $password);
    }

    protected function createApiUser(UserInterface $user, string $password=self::PASSWORD): ApiUser
    {
        return $this->apiClient->createApiUser($user, $password);
    }

    protected function getPassword(): string
    {
        return self::PASSWORD;
    }

    public function createEntityTracker(object $entity, array $serializerContext=[], array $serializerOptions=[]):EntityTracker
    {
        return $this->apiRequestService->createEntityTracker($entity, $serializerContext, $serializerOptions);
    }

    public function createAclTracker(AclInterface $entity, array $serializerContext=[], array $serializerOptions=[]):EntityTracker
    {
        return $this->createEntityTracker($entity, array_merge(['groups'=>'acl:write','operation'=>(new Get)->withClass(get_class($entity))], $serializerContext), $serializerOptions);
    }
    public function createAclMemberTracker(AclMemberInterface $entity, array $serializerContext=[], array $serializerOptions=[]):EntityTracker
    {
        return $this->createEntityTracker($entity, array_merge(['groups'=>'acl_member:write','operation'=>(new Get)->withClass(get_class($entity))], $serializerContext), $serializerOptions);
    }

    public function getApiRequestService(): ApiRequestService
    {
        return $this->apiRequestService;
    }

    protected function createAdminApiUser(ApiUser $systemApiUser, array $body=[], string $msg = 'Create admin user system user'): ApiUser
    {
        $this->debug('TEST: '.__FUNCTION__);

        $systemApiUser->getUser()->impersonate($this->createTenant($systemApiUser));
        $systemApiUser->authenticate();

        return $this->createApiUser(
            $systemApiUser
            ->post(TenantUser::class, array_merge(['password'=>$this->getPassword(), 'roles'=>[ResourcePermissionChecker::getUserRole(TenantUser::class, 'admin')]], $body))
            ->assert()
            ->log($msg)
            ->toEntity()
        );
    }
    public function createTenant(ApiUser $systemApiUser, array $body=[], string $msg = 'Create tenant by system user'): Tenant
    {
        $this->debug('TEST: '.__FUNCTION__);

        return $systemApiUser
        ->post(Tenant::class, $body)
        ->assert()
        ->log($msg)
        ->toEntity();
    }


    public function createTenantUserResponse(ApiUser $adminApiUser, array $body=[], string $msg = 'Create tenant user by admin user'): EntityResponse
    {
        $this->debug('TEST: '.__FUNCTION__);
        return $adminApiUser
        ->post(TenantUser::class, array_merge(['password'=>$this->getPassword(), 'roles'=>[ResourcePermissionChecker::getUserRole(TenantUser::class, 'user')]], $body))
        ->assert()
        ->log($msg);
    }

    public function createTenantUser(ApiUser $adminApiUser, array $body=[], string $msg = 'Create tenant user by admin user'): UserInterface
    {
        return $this->createTenantUserResponse($adminApiUser, $body, $msg)->toEntity();
    }

    public function createTenantApiUser(ApiUser $adminApiUser, array $body=[], string $msg = 'Create tenant user by admin user'): ApiUser
    {
        return $this->createApiUser($this->createTenantUser($adminApiUser, $body, $msg));
    }

    public function createVendorUserResponse(EntityResponse $vendorResponse, ApiUser $adminApiUser, array $body=[], string $msg = 'Create vendor user by admin user'): EntityResponse
    {
        $this->debug('TEST: '.__FUNCTION__);
        return $adminApiUser
        ->post(VendorUser::class, array_merge(['password'=>$this->getPassword(), 'roles'=>[ResourcePermissionChecker::getUserRole(VendorUser::class, 'user')], 'organization'=>$this->apiRequestService->createLinkFromResponse($vendorResponse)], $body))
        ->assert()
        ->log($msg);
    }

    public function createVendorUser(EntityResponse $vendorResponse, ApiUser $adminApiUser, array $body=[], string $msg = 'Create vendor user by admin user'): UserInterface
    {
        return $this->createVendorUserResponse($vendorResponse, $adminApiUser, $body, $msg)->toEntity();
    }

    public function createVendorApiUser(EntityResponse $vendorResponse, ApiUser $adminApiUser, array $body=[], string $msg = 'Create vendor user by admin user'): ApiUser
    {
        return $this->createApiUser($this->createVendorUser($vendorResponse, $adminApiUser, $body, $msg));
    }

    public function createVendorResponse(ApiUser $adminApiUser, array $body=[], string $msg = 'Create vendor by admin user'): EntityResponse
    {
        $this->debug('TEST: '.__FUNCTION__);

        return $adminApiUser
        ->post(Vendor::class, $body)
        ->assert()
        ->log($msg);
    }

    protected function getLastIdFromUri(string $uri): string
    {
        $parts = explode('/', $uri);
        return end($parts);
    }

    // Not used.
    protected function getApiUsersFromTenant(Tenant $tenant): array
    {
        foreach($tenant->getUsers() as $user) {
            if($user->isAdminUser()) {
                $adminApiUser = $this->createApiUser($user);
                continue;
            }
            if($user->isNormalUser()) {
                $tenantApiUser = $this->createApiUser($user);
                continue;
            }
        }
        return [$adminApiUser, $tenantApiUser, $this->createApiUser($tenant->getVendors()[0]->getUsers()[0])];
    }
    // Not used.
    protected function getAdminApiUserFromTenant(Tenant $tenant): ApiUser
    {
        foreach($tenant->getUsers() as $user) {
            if($user->isAdminUser()) {
                return $this->createApiUser($user);
                continue;
            }
        }
    }
    // Not used.
    protected function getTenantApiUserFromTenant(Tenant $tenant): ApiUser
    {
        foreach($tenant->getUsers() as $user) {
            if($user->isNormalUser()) {
                return $this->createApiUser($user);
                continue;
            }
        }
    }
    // Not used.
    protected function getVendorApiUserFromTenant(Tenant $tenant): ApiUser
    {
        return $this->createApiUser($tenant->getVendors()[0]->getUsers()[0]);
    }

    protected function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    abstract protected static function getAclPermissionsArray():array;

    protected static function getAclPermissionSets(bool $getUser):array
    {
        $permissionSets = [];
        $usedPermissionSets = [];
        foreach([true, false] as $setTenantPermission) {
            foreach(self::getAclPermissions() as $permission) {
                $permissionSet = AclPermissionSet::createFromAssociateArray(ResourcePermissionChecker::createDocumentPermissionSetArray());
                if($setTenantPermission) {
                    if($getUser) {
                        $permissionSet->setTenantUserPermission($permission);
                    }
                    else {
                        $permissionSet->setTenantMemberPermission($permission);
                    }
                }
                else {
                    if($getUser) {
                        $permissionSet->setVendorUserPermission($permission);
                    }
                    else {
                        $permissionSet->setVendorMemberPermission($permission);
                    }
                }
                $json = json_encode($permissionSet->toArray());
                if(in_array($json, $usedPermissionSets)) {
                    self::debug(sprintf('Skip duplicated resource permission: %s', $permissionSet->toCrudString()));
                    continue;
                }
                $usedPermissionSets[] = $json;
                $permissionSets[] = $permissionSet;
            }
        }
        return $permissionSets;
    }

    protected static function getAclPermissions():array
    {
        $permissions = [];
        foreach(static::getAclPermissionsArray() as $permission) {
            $permission = AclPermission::createFromArray($permission);
            try {
                $permission->validate();
            }
            catch(InvalidAclPermissionException $e) {
                //self::debug(sprintf('Skip invalid permission: %s %s', $e->getMessage(), $permission->toCrudString()));
                continue;
            }
            $permissions[] = $permission;
        }
        return $permissions;
    }

    // Debug logging
    protected static function debug(string $message):void
    {
        if(static::DEBUG_ECHO) {
            syslog(LOG_INFO, $message);
            echo(PHP_EOL.$message.PHP_EOL);
        }
    }

    protected function debugAclPermissionArray(AclPermission|AclPermissionSet ...$permissions):array
    {
        return array_map(function(AclPermission|AclPermissionSet $p){return $p->debug();}, $permissions);
    }
}