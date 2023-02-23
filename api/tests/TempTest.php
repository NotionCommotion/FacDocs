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

use App\Entity\User\SystemUser;
use App\Entity\Asset\Asset;
use App\Entity\Project\Project;
use App\Entity\Organization\Tenant;
//use App\Entity\Project\ProjectStage;
use App\Entity\Specification\CsiSpecification;
use App\Entity\Specification\SpecificationInterface;
use App\Entity\Document\DocumentStage;
use App\Entity\Document\DocumentType;
use App\Entity\Document\Media;
use App\Entity\Document\Document;
use App\Entity\User\UserInterface;
use App\Test\Model\AbstractTestCase;

class TempTest extends AbstractTestCase
{ 
    public function testFileUpload():Ulid
    {
        //$this->verbose = true;

        $adminUser = $this->getTestHelperService()->getTestingTenantUser('ROLE_TENANT_ADMIN');

        //$asset = $this->getTestHelperService()->getRandomTenantRecord($adminUser->getTenant(), Asset::class);
        //printf('$asset: %s'.PHP_EOL, $asset->getId());
        //$project = $this->getTestHelperService()->getRandomTenantRecord($adminUser->getTenant(), Project::class);
        //echo(json_encode($project->debug()).PHP_EOL);

        $assetId = $this->createAssetId($adminUser->getId());
        $projectId = $this->createProjectId($adminUser->getId(), $assetId);
        $project = $this->request($adminUser, 'GET', '/projects/'.$projectId)->toArray();
        $permission = array_intersect_key($project['accessControl']['documentPermission']['tenantUser'], array_flip(['read', 'create', 'update', 'delete']));
        printf('$projectId: %s with permission %s'.PHP_EOL, $projectId, $this->getTestHelperService()->arrayToString($permission));

        $user1 = $this->createUser($adminUser, 'ROLE_TENANT_USER');
        printf('$user1: %s'.PHP_EOL, $user1->getId());
        $user2 = $this->createUser($adminUser, 'ROLE_TENANT_USER');
        printf('$user2: %s'.PHP_EOL, $user2->getId());

        $file = UploadedMockFile::create(new fileTypes\Text(10000));
        $rsp = $this->uploadMedia($user1, $file);
        $mediaId = Ulid::fromString($rsp['id']);
        
        printf(PHP_EOL.'Wait for 5 seconds so that the media is stale.'.PHP_EOL);
        sleep(5);
        
        $document = $this->schemaBuilder->getRequestBody($user1->getTenant()->getId(), Document::class, ['media'=>$this->link('media', $mediaId), 'project'=>$this->link('projects', $projectId), 'assets'=>[$this->link('assets', $assetId)],]);

        $this->_(
            $user1, 'POST', '/documents', $document, 403,
            'Attempt to created new document by owning user %s using previously uploaded stale media %s',
            function(array $body, $rsp, $user) use ($mediaId){return [$user->getId(), $mediaId];}
        );
        
        $rsp = $this->uploadMedia($user1, $file);
        
        $mediaId = Ulid::fromString($rsp['id']);
        $document['media']=$this->link('media', $mediaId);

        $this->_(
            $user2, 'POST', '/documents', $document, 403,
            'Attempt to created new document by non-owning user %s using previously uploaded current media %s',
            function(array $body, $rsp, $user) use ($mediaId){return [$user->getId(), $mediaId];}
        );

        $document = $this->_(
            $user1, 'POST', '/documents', $document, 201,
            'Attempt to created new document by owning user %s using previously uploaded current media %s',
            function(array $body, $rsp, $user) use ($mediaId){return [$user->getId(), $mediaId];}
        )->toArray();

        $document = $this->_(
            $user2, 'POST', '/documents', $document, 403,
            'Attempt to created new document by non-owning user %s using previously uploaded current media %s',
            function(array $body, $rsp, $user) use ($mediaId){return [$user->getId(), $mediaId];}
        )->toArray();

        $documentId = $document['id'];
        $this->_(
            $user2, 'GET', '/documents/'.$documentId, [], 403,
            'Attempt to view new document %s which belongs to owner-read project by unauthorized user %s',
            function(array $body, $rsp, $user) use($documentId) {return [$documentId, $user->getId()];}
        );

        exit;

        $response = $this->request($user1, 'POST', '/documents', $document);
        //print_r($response);
        $this->assertResponseIsSuccessful();
        // Do some assertions on the Document
        $a = $response->toArray();
        //echo(json_encode($a).PHP_EOL);
        printf(PHP_EOL.'Created new document %s (%s) using previously uploaded media %s but did not add an asset'.PHP_EOL,
        $a['name'], $a['id'], $mediaId);

        $path = sprintf('/assets/%s/documents/%s', $assetId, $a['id']);
        $response = $this->request($adminUser, 'POST', $path);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Added document %s (%s) to previous created asset %s by admin user %s'.PHP_EOL, $a['name'], $a['id'], $assetId, $adminUser->getId());

        $response = $this->request($user1, 'POST', $path);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Added document %s (%s) to previous created asset %s by normal user %s'.PHP_EOL, $a['name'], $a['id'], $assetId, $user1->getId());

        $override['assets'] = [$this->link('assets', $assetId)];
        $document = $this->schemaBuilder->getRequestBody($user1->getTenant()->getId(), Document::class, $override);
        $response = $this->request($user1, 'POST', '/documents', $document);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Created another new document %s (%s) using previously uploaded media %s but this time added asset %s'.PHP_EOL, $a['name'], $a['id'], $mediaId, $assetId);

        $response = $this->request($user2, 'POST', '/documents', $document);
        //$this->onNotSuccessfulTest();
        printf(PHP_EOL.'Attempt to upload a new document by user %s using previously uploaded media %s which was uploaded by user %s'.PHP_EOL, $user2->getId(), $mediaId, $user1->getId());
        $this->assertResponseStatusCodeSame(403);
        
        print_r($project);
        $this->getTestHelperService()->setDocumentAclPermission($project, 'tenantUserPermission', 'read', 'all');
        $response = $this->request($adminUser, 'PUT', '/projects/'.$project['id'], $project);
        printf(PHP_EOL.'Change the project to have public read access'.PHP_EOL);
        print_r($response->getInfo());
        exit;

        $response = $this->request($user2, 'POST', '/documents', $document);
        //$this->onNotSuccessfulTest();
        printf(PHP_EOL.'Attempt to upload a new document by user %s using previously uploaded media %s which was uploaded by user %s'.PHP_EOL, $user2->getId(), $mediaId, $user1->getId());
        $this->assertResponseStatusCodeSame(403);

        $response = $this->request($user1, 'GET', $a['@id']);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Media::class);
        $a = $response->toArray();
        printf(PHP_EOL.'Download media %s by media owner tenant user %s'.PHP_EOL, $a['id'], $user2->getId());
        echo(json_encode($response->toArray()).PHP_EOL);

        $response = $this->request($user2, 'GET', $a['@id']);
        $this->assertResponseIsSuccessful();
        $this->assertMatchesResourceItemJsonSchema(Media::class);
        $a = $response->toArray();
        printf(PHP_EOL.'Download media %s by non-media owner tenant user %s'.PHP_EOL, $a['id'], $user2->getId());
        echo(json_encode($response->toArray()).PHP_EOL);

        return Ulid::fromString($a['id']);

    }

    private function uploadMedia(UserInterface $user, ?UploadedMockFile $file = null): array
    {
        $file = $file??UploadedMockFile::create(new fileTypes\Text(10000));
        return $this->__(
            $user, '/media', $file, 201,
            'Upload media %s by tenant user %s (%s). New media: %s (%s)',
            function(array $rsp, UserInterface $user, UploadedMockFile $file){
                return [$file->getFileType()->getDescription(), $user->getId(), $user->getTenant()->getId(), $rsp['mediaType'], Ulid::fromString($rsp['id']),];
            }
        )->toArray();
    }
    private function createProjectId(Ulid $userId, Ulid $assetId): Ulid
    {
        $user = $this->getTestHelperService()->getUserById($userId);

        $override = [
            'defaultAsset'=>$this->link('assets', $assetId),
        ];
        $project = $this->schemaBuilder->getRequestBody($user->getTenant()->getId(), Project::class, $override);
        $project['name']=$project['name'].'_'.time();
        $this->getTestHelperService()
        ->setDocumentAclPermission($project, 'tenantUserPermission', 'create', 'all')
        ->setDocumentAclPermission($project, 'tenantUserPermission', 'read', 'owner')
        ;
        $response = $this->request($user, 'POST', '/projects', $project);
        $this->assertResponseIsSuccessful();
        $a = $response->toArray();
        printf(PHP_EOL.'Create project by tenant user %s (%s) belonging to %s (%s).  New project: %s (%s)'.PHP_EOL, $user->getFullName(), $user->getId(), $user->getTenant()->getName(), $user->getTenant()->getId(), $a['name'], $a['id']);
        return Ulid::fromString($a['id']);
    }

    private function createAssetId(Ulid $userId):Ulid
    {
        $user = $this->getTestHelperService()->getUserById($userId);
        $asset = $this->schemaBuilder->getRequestBody($user->getTenant()->getId(), Asset::class);
        $asset['name']=$asset['name'].'_'.time();
        $response = $this->request($user, 'POST', '/assets', $asset);
        $a = $response->toArray();
        printf(PHP_EOL.'Create asset by tenant user %s (%s) belonging to %s (%s).  New asset: %s (%s)'.PHP_EOL, $user->getFullName(), $user->getId(), $user->getTenant()->getName(), $user->getTenant()->getId(), $a['name'], $a['id']);
        $this->assertResponseIsSuccessful();
        return Ulid::fromString($a['id']);
    }
}
