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

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Symfony\Bundle\Test\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

use App\Test\Model\UploadedMockFile;
use App\Test\Model\FileTypes;

use App\Test\Model\Api\TestUserContainer;
use App\Test\Model\Api\EntityContainer;
use App\Test\Service\ApiRequestService;

use App\Entity\User\SystemUser;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\Asset\Asset;
use App\Entity\Project\Project;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\Vendor;
//use App\Entity\Project\ProjectStage;
use App\Entity\Specification\CsiSpecification;
use App\Entity\Specification\SpecificationInterface;
use App\Entity\Document\DocumentStage;
use App\Entity\Document\DocumentType;
use App\Entity\Document\Media;
use App\Entity\Document\Document;
use App\Entity\User\UserInterface;
use App\Test\Model\AbstractTestCase;


class AppTest extends AbstractTestCase
{
    //use RefreshDatabaseTrait;
    use EntityContainerTrait;

    protected const DEBUG = true;   // Set to true to echo request/response info.
    protected const LOG_FILE = '/var/www/facdocs/api/public/tests/_resource_acl_test.html';

    protected static function getLogOutputFile():string
    {
        return self::LOG_FILE;
    }

    public function testTenantUser():ApiRequestService
    {
        self::addMessageLogItem('TenantUser testing.');
        $rootUserRequester = self::getRootUserRequestService();

        $tenantUser = $rootUserRequester
        ->post(TenantUser::class, ['roles'=>['ROLE_TENANT_USER'], 'password'=>$this->getPassword()])
        ->assert(201)
        ->log('Created tenant user.')
        ->toEntity();
        
        return $this->completeUserTest($rootUserRequester, $tenantUser);
    }

    public function dfsstestVendor():Vendor
    {
        $rootUserRequester = self::getRootUserRequestService();

        $vendor = $rootUserRequester->post(Vendor::class)->assert(201)->log('Add first vendor.')->toEntity();
        return $vendor;
    }

    /**
     * @depends testVendor
     */
    public function xstestVendorUser(Vendor $vendor)
    {
        $entityContainer = self::getEntityContainer();
        $rootUserRequester = self::getApiRequestService($entityContainer->getRootUser());
        $vendor = $entityContainer->getEntity(Vendor::class, 0);
        $entityContainer->addEntity($vendorUser);
    }

    private function completeUserTest(ApiRequestService $rootUserRequester, UserInterface $user):ApiRequestService
    {
        $userTracker = $this->createEntityTracker($user);
        
        $userGet = $rootUserRequester
        ->getItem($userTracker->getClass(), $userTracker->getId())
        ->assert(200)
        ->log(sprintf('Get %s user.', $user->getType()->name));

        $userTracker->setEmail('PUT_testTenantUser@bla.com');
        $userModified1 = $rootUserRequester
        ->put($userTracker->getClass(), $userTracker->getId(), $userTracker->diff())
        ->assert(200)
        ->log(sprintf('Modify %s user using PUT request.', $user->getType()->name));

        $userTracker->setEmail('yyyy@bla.com');
        $userModified2 = $rootUserRequester
        ->patch($userTracker->getClass(), $userTracker->getId(), $userTracker->diff())
        ->assert(200)
        ->log(sprintf('Modify %s user using PATCH request.', $user->getType()->name));

        $rootUserRequester
        ->delete($userTracker->getClass(), $userTracker->getId())
        ->assert(204)
        ->log(sprintf('Deleted %s user.', $user->getType()->name));
        
        return $rootUserRequester;
    }

    public function xtestValidLogon(): void
    {                                                      
        echo(PHP_EOL.__FUNCTION__.PHP_EOL);
        foreach($this->getTestHelperService()->getAllTestingUsers() as $user) {
            $credentials = $user->getLogon($this->getTestHelperService()->getPassword());
            $response = $this->authenticate($credentials);
            $this->assertResponseIsSuccessful();
        }
    }

    public function xtestInvalidPasswordLogon(): void
    {
        echo(PHP_EOL.__FUNCTION__.PHP_EOL);
        foreach($this->getTestHelperService()->getAllTestingUsers() as $user) {
            $credentials = $user->getLogon('InvalidPassword');
            $response = $this->authenticate($credentials);
            $this->assertJsonContains(['message' => 'Invalid credentials.']);
            $this->assertResponseStatusCodeSame(401);
        }
    }

    public function xtestInvalidIdLogon(): void
    {
        echo(PHP_EOL.__FUNCTION__.PHP_EOL);
        $id = new Ulid();
        foreach($this->getTestHelperService()->getAllTestingUsers() as $user) {
            $credentials = $user->getLogon($this->getTestHelperService()->getPassword());
            $credentials['id'] = $id;
            $response = $this->authenticate($credentials);
            if($user instanceof SystemUser) {
                // System users don't need the ID.
                $this->assertJsonContains(['message' => 'System user found but not tenant.']);
                $this->assertResponseStatusCodeSame(401);
            }
            else {
                $this->assertJsonContains(['message' => 'Invalid credentials.']);
                $this->assertResponseStatusCodeSame(401);
            }
        }
    }

    public function xxxtestGet():void
    {
        echo(PHP_EOL.'Test getting record collections and specific records'.PHP_EOL);
        $user = $this->getTestHelperService()->getTestingTenantUser('ROLE_TENANT_ADMIN');
        foreach(['documents', 'assets', 'document_groups', 'specifications'=>'spec', 'csi_specifications'=>'spec', 'custom_specifications', 'help_desk_posts'=>'id', 'help_desk_topics'=>'subject', 'projects', 'job_titles', 'naics_codes'=>'title', 'project_directories', 'project_directory_members', 'project_members', 'departments','document_stages', 'document_types', 'project_stages'] as $path=>$name) {
            // Get collection
            if(is_int($path)) {
                $path = $name;
                $name = 'name';
            }
            $collectionResponse = $this->request($user, 'GET', $path, [], ['accept'=>'application/json']);
            $this->assertResponseIsSuccessful();
            $collectionArray = $collectionResponse->toArray();
            if(count($collectionArray)) {
                // Get item
                $itemResponse = $this->request($user, 'GET', $path.'/'.$collectionArray[rand(0, count($collectionArray)-1)]['id'], [], ['accept'=>'application/json']);
                $this->assertResponseIsSuccessful();
                $itemArray = $itemResponse->toArray();
                printf('    %s collection: %s records.  %s item for ID %s: %s = %s'.PHP_EOL, $path, count($collectionArray), $path, $itemArray['id'], $name, $itemArray[$name]);
            }
            else {
                printf('    %s collection: %s records: %s item: N/A'.PHP_EOL, $path, count($collectionArray), $path);
            }
        }
        // Need to figure out media_types
        $path = 'media_types';
        $collectionResponse = $this->request($user, 'GET', $path, [], ['accept'=>'application/json']);
        $this->assertResponseIsSuccessful();
        $collectionArray = $collectionResponse->toArray();
        // Get item
        $item = $collectionArray[rand(0, count($collectionArray)-1)];
        $itemResponse = $this->request($user, 'GET', sprintf('/%s/type=%s;subtype=%s', $path, $item['type'], $item['subtype']), [], ['accept'=>'application/json']);
        $this->assertResponseIsSuccessful();
        $itemArray = $itemResponse->toArray();
        printf('    %s collection: %s records. %s item for ID %s: %s = %s'.PHP_EOL, $path, count($collectionArray), $path, $itemArray['id'], $name, $itemArray[$name]);
    }

    public function abctestCreateTenant()
    {
        $tenant = $this->getSchemaFixtureService()->getTenant();

        $user = $this->getTestHelperService()->getTestingTenantUser('ROLE_TENANT_ADMIN');
        $response = $this->request($user, 'POST', '/tenants', $tenant);
        $this->assertResponseStatusCodeSame(403);
        printf(PHP_EOL.'Attempt to create tenant by tenant user %s with roles %s'.PHP_EOL, $user->getId(), implode(', ', $user->getRoles()));

        $user = $this->getTestHelperService()->getTestingSystemUser('ROLE_SYSTEM_ADMIN');
        $response = $this->request($user, 'POST', '/tenants', $tenant);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Create tenant by system user %s with roles %s.  Tenant: %s (%s)'.PHP_EOL, $user->getId(), implode(', ', $user->getRoles()), $a['name'], $a['id']);
        return Ulid::fromString($a['id']);
    }

    /**
     * @depends testCreateTenant
     */
    public function abctestCreateUser(Ulid $tenantId):Ulid
    {
        $systemUser = $this->getTestHelperService()->getTestingSystemUser('ROLE_SYSTEM_ADMIN')->impersonate($this->getTestHelperService()->getTenantById($tenantId));

        $user1 = $this->createUserResponse($systemUser, 'ROLE_TENANT_ADMIN');
        $this->assertResponseIsSuccessful();
        $a = $user1->toArray();
        printf(PHP_EOL.'Create tenant user by system user %s impostering as tenant user of %s (%s).  New user: %s %s (%s)'.PHP_EOL, $systemUser->getId(), $systemUser->getTenant()->getName(), $systemUser->getTenant()->getId(), $a['firstName'], $a['lastName'], $a['id']);
        $user1 = $this->getTestHelperService()->getUserById(Ulid::fromString($a['id']));

        $user2 = $this->createUserResponse($user1, 'ROLE_TENANT_ADMIN');
        $this->assertResponseIsSuccessful();
        $a = $user2->toArray();
        printf(PHP_EOL.'Create tenant user by tenant user %s (%s) belonging to %s (%s).  New user: %s %s (%s)'.PHP_EOL, $user1->getFullName(), $user1->getId(), $user1->getTenant()->getName(), $user1->getTenant()->getId(), $a['firstName'], $a['lastName'], $a['id']);

        return Ulid::fromString($a['id']);
    }

    /**
     * @depends testCreateUser
     */
    public function abctestCreateAssetResource(Ulid $userId):Ulid
    {
        $user = $this->getTestHelperService()->getUserById($userId);
        $asset = $this->getSchemaFixtureService()->getRequestBody($user->getTenant()->getId(), Asset::class);
        $asset['name']=$asset['name'].'_'.time();
        $response = $this->request($user, 'POST', '/assets', $asset);
        $a = $response->toArray();
        printf(PHP_EOL.'Create asset by tenant user %s (%s) belonging to %s (%s).  New asset: %s (%s)'.PHP_EOL, $user->getFullName(), $user->getId(), $user->getTenant()->getName(), $user->getTenant()->getId(), $a['name'], $a['id']);
        $this->assertResponseIsSuccessful();
        return Ulid::fromString($a['id']);
    }

    /**
     * @depends testCreateUser
     * @depends testCreateAssetResource
     */
    public function abctestProjectResource(Ulid $userId, Ulid $assetId): Ulid
    {
        $user = $this->getTestHelperService()->getUserById($userId);

        $override = [
            'defaultAsset'=>$this->link('assets', $assetId),
        ];
        $project = $this->getSchemaFixtureService()->getRequestBody($user->getTenant()->getId(), Project::class, $override);
        $project['name']=$project['name'].'_'.time();
        $project['aclPermissionSet']['tenantDefaultPermission'] = $this->getAclPermission('public'); //so we can test a non-admin tenant user uploading a file.
        $response = $this->request($user, 'POST', '/projects', $project);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Create project by tenant user %s (%s) belonging to %s (%s).  New project: %s (%s)'.PHP_EOL, $user->getFullName(), $user->getId(), $user->getTenant()->getName(), $user->getTenant()->getId(), $a['name'], $a['id']);
        return Ulid::fromString($a['id']);
    }

    /**
     * @depends testCreateUser
     * @depends testCreateAssetResource
     * @depends testProjectResource
     */
    public function abctestDocumentResource(Ulid $userId, Ulid $assetId, Ulid $projectId)
    {
        $adminUser = $this->getTestHelperService()->getUserById($userId);

        $user1 = $this->createUser($adminUser, 'ROLE_TENANT_USER');
        $user2 = $this->createUser($adminUser, 'ROLE_TENANT_USER');

        $fileType=new fileTypes\Text(10000);
        $file = UploadedMockFile::create($fileType);

        $response = $this->sendFile($user1, '/media', $file, 'multipart/form-data');
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Media::class);
        $a = $response->toArray();
        $mediaId = Ulid::fromString($a['id']);
        printf(PHP_EOL.'Upload media %s by tenant user %s (%s). New media: %s (%s)'.PHP_EOL, $fileType->getDescription(), $user1->getId(), $user1->getTenant()->getId(), $a['mediaType'], $mediaId);

        $override = [
            'media'=>$this->link('media', $mediaId),
            'project'=>$this->link('projects', $projectId),
            //'assets'=>[$this->link('assets', $assetId)],
        ];
        $document = $this->getSchemaFixtureService()->getRequestBody($user1->getTenant()->getId(), Document::class, $override);

        $response = $this->request($user1, 'POST', '/documents', $document);
        $this->assertResponseIsSuccessful();
        // Do some assertions on the Document
        $a = $response->toArray();
        printf(PHP_EOL.'Created new document %s (%s) using previously uploaded media %s but did not add an asset'.PHP_EOL, $a['name'], $a['id'], $mediaId);

        $response = $this->request($user1, 'POST', sprintf('%s/assets/%s', $a['@id'], $assetId));
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Added asset %s to previous created document %s (%s)'.PHP_EOL, $assetId, $a['name'], $a['id']);

        $override['assets'] = [$this->link('assets', $assetId)];
        $document = $this->getSchemaFixtureService()->getRequestBody($user1->getTenant()->getId(), Document::class, $override);
        $response = $this->request($user1, 'POST', '/documents', $document);
        print_r($response->getInfo());
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Created another new document %s (%s) using previously uploaded media %s but this time added asset %s'.PHP_EOL, $a['name'], $a['id'], $mediaId, $assetId);

        $response = $this->request($user2, 'POST', '/documents', $document);
        print_r($response->getInfo());
        $this->assertResponseIsNotSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Created new document %s (%s) using previously uploaded media %s but by another user'.PHP_EOL, $a['name'], $a['id'], $user2);
        print_r($response->toArray());
        // Unsucesssful



        $response = $this->request($user1, 'GET', $a['@id']);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Media::class);
        $a = $response->toArray();
        printf(PHP_EOL.'Download media %s by media owner tenant user %s'.PHP_EOL, $a['id'], $user2->getId());
        print_r($response->toArray());

        $response = $this->request($user2, 'GET', $a['@id']);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Media::class);
        $a = $response->toArray();
        printf(PHP_EOL.'Download media %s by non-media owner tenant user %s'.PHP_EOL, $a['id'], $user2->getId());
        print_r($response->toArray());

        return Ulid::fromString($a['id']);

    }
}
