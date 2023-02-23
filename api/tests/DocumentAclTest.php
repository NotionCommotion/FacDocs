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

use App\Test\Model\Api\ApiUser;
use App\Test\Model\Api\EntityTracker;
use App\Test\Model\Api\ResourcePermissionChecker;
use App\Test\Model\Api\FileResponse;
use App\Test\Model\AbstractTestCase;
use App\Entity\User\UserInterface;

use App\Test\Model\Api\DocumentUserCreator;

use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\Archive\Archive;
use App\Entity\Project\Project;
use App\Entity\Asset\Asset;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\AclPermissionEnum;
use App\Entity\Acl\AclInterface;
use App\Entity\Acl\DocumentAclInterface;
use App\Entity\Acl\HasDocumentAclInterface;
use App\Test\Model\UploadedMockFile;
use App\Test\Model\FileTypes;

//use App\Entity\Project\ProjectStage;
use App\Entity\Specification\CsiSpecification;
use App\Entity\Specification\SpecificationInterface;
use App\Entity\Document\DocumentStage;
use App\Entity\Document\DocumentType;
use App\Entity\Document\Media;
use App\Entity\Document\Document;
use App\Test\Model\Api\EntityResponse;
use App\Test\Model\Api\DocumentAuthorizationStatus;

use Symfony\Component\Uid\Ulid;

//use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/*
Questions:
1. When updating user member's roles, if no data, returns 403.  Why?
2. For unknown reason, sometimes need to reauthenticate user client.
*/

class DocumentAclTest extends AbstractTestCase
{
    //use RefreshDatabaseTrait;

    protected const LOG_FILE = '/var/www/facdocs/api/public/tests/_document_acl_test.html';

    public const PERMISSION_MAP = [
        'NONE'      => 0b000,
        'ALL'       => 0b001,
        'OWNER'     => 0b010,
        'COWORKER'  => 0b011,
        'VENDOR'    => 0b100,
        //'FUTURE'  => 0b101,
    ];

    public const PERMISSION_ACTION_MAP = ['CREATE', 'READ', 'UPDATE', 'DELETE'];

    protected const API_CLIENT_OPTIONS = ['echoLog'=>true, 'debug'=>false];
    protected const DEBUG_ECHO = true;

    protected static function getLogOutputFile():string
    {
        syslog(LOG_INFO, 'start1');
        return self::LOG_FILE;
    }
    protected static function getDbTestName():string
    {
        return 'DocumentTest - '.time();
    }

    public function testDocumentPermissions():void
    {
        $adminApiUser = $this->createAdminApiUser($this->getSystemApiUser());

        $projectResponse = $adminApiUser
        ->post(Project::class)
        ->assert()
        ->log('Admin user creates project for the test');

        ['documentAcl' => $documentAclUri, '@id' => $projectUri, 'resourceAcl' => $resourceAclUri/*, 'id' => $projectId*/] = $projectResponse->toArray();

        $project = $projectResponse->toEntity();
        $documentAclTracker = $this->createAclTracker($project->getDocumentAcl());
        $documentAclTracker->getPermissionSet()->setToNoAccess();
        $resourceAclTracker = $this->createAclTracker($project->getResourceAcl());
        $resourceAclTracker->getPermissionSet()->setToNoAccess();

        $adminApiUser
        ->customRequest('put', $resourceAclUri, $resourceAclTracker->normalize())
        ->assert()
        ->log('Admin user updated project\'s resource ACL permission to no access.');

        $documentTenantUserCreator = new DocumentUserCreator($adminApiUser, $projectUri, TenantUser::class, $this->getPassword());
        $documentVendorUserCreator = new DocumentUserCreator($adminApiUser, $projectUri, VendorUser::class, $this->getPassword());

        $documentAclTracker    // Should't be necessary.
        ->removeUserAsMember($documentTenantUserCreator->getSubjectApiUser()->getUser())
        ->removeUserAsMember($documentVendorUserCreator->getSubjectApiUser()->getUser());

        $userPermissionSets = $this->getAclPermissionSets(true);
        $this->debug(sprintf('Test Non-Member ACL permissions PermissionSets: %d', count($userPermissionSets)));

        // Testing.  REMOVE!!!!!!!!!!!!
        //$userPermissionSets = [$userPermissionSets[3]];
        //$this->debug(sprintf('Test Non-Member ACL permissions PermissionSets: %d', count($userPermissionSets)));

        $this->debug('Test Non-Member ACL permissions');
        foreach($userPermissionSets as $i=>$permissionSet) {
            $this->debug(sprintf('User Loop %d of %d', $i+1, count($userPermissionSets)));
            $documentAclTracker->setPermissionSet($permissionSet);

            $adminApiUser
            ->customRequest('put', $documentAclUri, $documentAclTracker->normalize())
            ->assert()
            ->log(sprintf('Admin user updated project\'s document ACL permission to %s.', $documentAclTracker->getPermissionSet()->toCrudString()));

            foreach($documentTenantUserCreator as $subjectApiUser=>$documentOwnerUser) {
                $this->_testUserPermission($subjectApiUser, $project, $documentOwnerUser, $documentTenantUserCreator);
            }
            foreach($documentVendorUserCreator as $subjectApiUser=>$documentOwnerUser) {
                $this->_testUserPermission($subjectApiUser, $project, $documentOwnerUser, $documentVendorUserCreator);
            }
        }
        $this->debug('Test Non-Member ACL permissions complete');

        $documentAclTracker->getPermissionSet()->setToNoAccess();
        $adminApiUser
        ->customRequest('put', $documentAclUri, $documentAclTracker->normalize())
        ->assert()
        ->log(sprintf('Admin user updated project\'s document ACL permission to no access.'));

        $tenantMemberTracker = $this->addMember($project, $projectUri, $documentTenantUserCreator, $adminApiUser);
        $vendorMemberTracker = $this->addMember($project, $projectUri, $documentVendorUserCreator, $adminApiUser);

        $memberPermissionSets = $this->getAclPermissionSets(false);
        $memberPermissions = $this->getAclPermissions();

        $this->debug(sprintf('Test Member ACL permissions memberPermissionSets: %d memberPermissions %d', count($memberPermissionSets), count($memberPermissions)));

        // Testing.  REMOVE!!!!!!!!!!!!
        //$memberPermissionSets = [$memberPermissionSets[3]];
        //$memberPermissions = [$memberPermissions[3]];
        //$this->debug(sprintf('Test Member ACL permissions memberPermissionSets: %d memberPermissions %d', count($memberPermissionSets), count($memberPermissions)));

        foreach($memberPermissionSets as $i=>$permissionSet) {
            $documentAclTracker->setPermissionSet($permissionSet);

            $this->debug(json_encode([
                'get'=>$adminApiUser->customRequest('get', $documentAclUri)->toArray(),
                'diff'=>$documentAclTracker->diff(true),
                'normalize'=>$documentAclTracker->normalize(),
            ]));
            $adminApiUser
            //->customRequest('put', $documentAclUri, $documentAclTracker->normalize())
            ->customRequest('put', $documentAclUri, $documentAclTracker->diff())
            ->assert()
            ->log(sprintf('Admin user updated project\'s document ACL permission to %s.', $documentAclTracker->getPermissionSet()->toCrudString()));

            foreach($memberPermissions as $j=>$memberPermission) {
                $this->debug(sprintf('Member Loop %d of %d | subloop %d of %d', $i+1, count($memberPermissionSets), $j+1, count($memberPermissions)));
                $this
                ->setMemberPermission($adminApiUser, $tenantMemberTracker, $memberPermission, $projectUri)
                ->setMemberPermission($adminApiUser, $vendorMemberTracker, $memberPermission, $projectUri);

                foreach($documentTenantUserCreator as $subjectApiUser=>$documentOwnerUser) {
                    $this->_testUserPermission($subjectApiUser, $project, $documentOwnerUser, $documentTenantUserCreator);
                }
                foreach($documentVendorUserCreator as $subjectApiUser=>$documentOwnerUser) {
                    $this->_testUserPermission($subjectApiUser, $project, $documentOwnerUser, $documentVendorUserCreator);
                }
            }
        }
    }

    private function setMemberPermission(ApiUser $adminApiUser, EntityTracker $memberTracker, AclPermission $memberPermission, string $projectUri):self
    {
        $memberTracker->setPermission($memberPermission);
        $user = $memberTracker->getEntity()->getUser();
        $adminApiUser
        ->customRequest('put', $this->getMemberPath($projectUri, $user), array_diff_key($memberTracker->normalize(), ['allowedSpecification'=>null]))
        ->assert()
        ->log(sprintf('Admin updated %s document members ACL permission to %s.', $this->getShortName($user::class), $memberPermission->toCrudString()));
        return $this;
    }

    private function getMemberPath(string $projectUri, UserInterface $user):string
    {
        return sprintf('%s/users/%s/documentMember', $projectUri, $user->getId());
    }

    private function addMember(HasDocumentAclInterface $project, string $projectUri, DocumentUserCreator $documentUserCreator, ApiUser $adminApiUser):EntityTracker
    {
        $user = $documentUserCreator->getSubjectApiUser()->getUser();
        $memberPath = $this->getMemberPath($projectUri, $user);

        $memberTracker = $this->createAclMemberTracker(
            $adminApiUser
            ->customRequest('post', $memberPath, [], ['populateBody'=>false])
            ->assert()
            ->log(sprintf('Admin user adds %s as a member to %s.', $this->getShortName($user::class), $projectUri))
            ->toEntity()
        );
        $project->getDocumentAcl()->addMember($memberTracker->getEntity());
        return $memberTracker;
    }

    private function _testUserPermission(ApiUser $subjectApiUser, HasDocumentAclInterface $project, UserInterface $documentOwnerUser, DocumentUserCreator $documentUserCreator):void
    {
        $this->debug(sprintf('CRUD TEST START: %s', $project->getDocumentAcl()->getPermissionSet()->toCrudString()));

        $subjectApiUser->authenticate();    // Why is this required?

        $testDocument = $documentUserCreator->getDocument($documentOwnerUser);

        $documentUri = $documentUserCreator->getDocumentUri($documentOwnerUser);
        $mediaUri = $documentUserCreator->getMediaUri($documentOwnerUser);

        //printf('xxxxxxxxxxx: getAnticipatedStatusCode: %s isAuthorized: %s'.PHP_EOL, $statusCreate->getAnticipatedStatusCode(), $statusCreate->isAuthorized()?'y':'n');

        $statusCreate = $this->createDocumentAuthorizationStatus('CREATE', $subjectApiUser->getUser(), $project, $testDocument);
        $subjectApiUser->
        uploadInitialDocument($documentUserCreator->getResourceUri())
        ->setAuthorizationStatus($statusCreate)
        ->assert()
        ->log('Attempt to create document');

        //print_r($documentUserCreator->getAdminApiUser()->getItem(Document::class, $testDocument->getId())->log('testing')->toArray());

        $statusRead = $this->createDocumentAuthorizationStatus('READ', $subjectApiUser->getUser(), $project, $testDocument);
        $subjectApiUser
        ->getItem(Document::class, $testDocument->getId())
        ->setAuthorizationStatus($statusRead)
        ->assert()
        ->log('Attempt to read document');

        //$subjectApiUser->customRequest('get', $documentUri)->log('get document '.$documentUri)->assert()->toArray();
        // Since user can read the document containing the media, they can read the media.  API doesn't know this and must check differently against all documents.
        $subjectApiUser
        ->download($mediaUri)
        ->setAuthorizationStatus($statusRead)
        ->assert()
        ->assertSameSize($testDocument->getMedia()->getSize())
        ->assertSameMediaType($testDocument->getMedia()->getPhysicalMedia()->getMediaType()->getType())
        ->log('Attempt to download from media');

        $subjectApiUser
        ->download($documentUri.'/download')
        ->setAuthorizationStatus($statusRead)
        ->assert()
        ->assertSameSize($testDocument->getMedia()->getSize())
        ->assertSameMediaType($testDocument->getMedia()->getPhysicalMedia()->getMediaType()->getType())
        ->log('Attempt to download from media from document');

        $statusUpdate = $this->createDocumentAuthorizationStatus('UPDATE', $subjectApiUser->getUser(), $project, $testDocument);
        $subjectApiUser
        ->put(Document::class, $testDocument->getId(), [], ['populateBody'=>true])
        ->setAuthorizationStatus($statusUpdate)
        ->assert()
        ->log('Attempt to update document');

        $addedMediaResponse = $subjectApiUser
        ->addMockMediaToDocument($documentUri)
        ->setAuthorizationStatus($statusUpdate)
        ->assert($statusUpdate->isAuthorized()?201:403)
        ->log('Attempt to add media to existing document');

        if($statusUpdate->isAuthorized()){
            //print_r($addedMediaResponse->toArray());
            //printf('$documentUri: %s $mediaUri: %s'.PHP_EOL, $documentUri, $mediaUri);
            $subjectApiUser
            ->customRequestWithClass('delete', $documentUri.$addedMediaResponse->toArray()['media'], Document::class)
            ->setAuthorizationStatus($statusUpdate)
            ->assert(204)
            ->log('Attempt to delete media from existing document');
        }
        else {
            $subjectApiUser
            ->customRequestWithClass('delete', $documentUri.$mediaUri, Document::class)
            ->setAuthorizationStatus($statusUpdate)
            ->assert(403)
            ->log('Attempt to delete media from existing document');
        }

        $statusDelete = $this->createDocumentAuthorizationStatus('DELETE', $subjectApiUser->getUser(), $project, $testDocument);
        $subjectApiUser
        ->delete(Document::class, $testDocument->getId())
        ->setAuthorizationStatus($statusDelete)
        ->assert()
        ->log('Attempt to delete document');

        if($statusDelete->isAuthorized()){
            $documentUserCreator->deleteUserDocument($documentOwnerUser);
        }
        $this->debug('CRUD TEST COMPLETE.');
    }

    protected static function getTotalLogCount(): ?int
    {
        $permissionSets = count(self::getAclPermissionSets(true));
        $permissions = count(self::getAclPermissions());
        return
        +2                      //Create tenant and AdminUser
        +1                      // Create Project
        +1                      //Update project permission
        +2*12                   //Create two DocumentUserCreators
        // User tests
        + $permissionSets * (
            +1                  //Update permission
            + 2*3*9             //tenant/vendor three user types test them.
            + 1                 // Allowance of new documents which were deleted.
        )
        + 1                     //Reset permissions
        + 2*1                   //Add members
        // Member tests
        + $permissionSets * (
            +1                  //Update permission
            +2                  //Update member permission
            + $permissions * (
                + 2             // Update member permissions.
                + 2*3*9         //tenant/vendor three user types test them.
                + 1             // Allowance of new documents which were deleted.
            )
        );
    }

    public function xtestStaleMedia()
    {
        return;
        $adminApiUser = $this->createAdminApiUser($this->getSystemApiUser());

        $document = $adminApiUser->uploadInitialDocument($resourceUri)->assert()->log('No sleep');
        $document = $adminApiUser->uploadInitialDocument($resourceUri, ['sleep'=>1])->assert()->log('Sleep 1 second');
        $document = $adminApiUser->uploadInitialDocument($resourceUri, ['sleep'=>6])->assert(403)->log('Sleep 6 second');
    }

    public function xtestRoleAccess()
    {
        return;
    }

    public function xtestInvalidCreatePermissions()
    {
        // i.e. owner, coworker.
        return;
    }

    private function createDocumentAuthorizationStatus(string $action, UserInterface $subjectUser, HasDocumentAclInterface $project, Document $testDocument):DocumentAuthorizationStatus
    {
        return new DocumentAuthorizationStatus($action, $subjectUser, $project, $testDocument, $this->apiRequestService->getAccessRoleService());
    }

    protected static function getAclPermissionsArray():array
    {
        $bits = 4*3;
        $max = (1 << $bits);
        $map = [0b000=>'NONE', 0b001=>'ALL', 0b010=>'OWNER', 0b011=>'COWORKER'];    //,  0b100=>'VENDOR'];

        $permissions = [];
        for ($i = 0; $i < $max; $i++) {
            if(($c = $map[$i>>0 & 0b111]??null) && ($r = $map[$i>>3 & 0b111]??null) && ($u = $map[$i>>6 & 0b111]??null) && ($d = $map[$i>>9 & 0b111]??null) && in_array($c, ['NONE', 'ALL'])) {
                $permissions[] = ['CREATE'=>$c, 'READ'=>$r, 'UPDATE'=>$u, 'DELETE'=>$d];
            }
        }
        return $permissions;
    }
}
