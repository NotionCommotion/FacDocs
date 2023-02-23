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
use App\Test\Model\AbstractTestCase;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\Asset\Asset;
use App\Entity\Archive\Archive;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\ResourceAclInterface;
use App\Entity\Acl\HasResourceAclInterface;
//use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

/*
Questions:
1. When updating user member's roles, if no data, returns 403.  Why?
2. For unknown reason, sometimes need to reauthenticate user client.
*/

class ResourceAclTest extends AbstractTestCase
{
    //use RefreshDatabaseTrait;

    protected const LOG_FILE = '/var/www/facdocs/api/public/tests/_resource_acl_test.html';

    protected const API_CLIENT_OPTIONS = ['echoLog'=>true, 'debug'=>false];
    protected const DEBUG_ECHO = true;

    protected static function getLogOutputFile():string
    {
        return self::LOG_FILE;
    }
    protected static function getDbTestName():string
    {
        return 'ResourceTest - '.time();
    }

    /**
     * @dataProvider getTestResourcePermissionData
     */
    public function testResourcePermissions(string $resourceClass):void
    {
        //print_r(ResourcePermissionChecker::getUserAclPermissionSets());exit;
        $this->debug(sprintf('%sBEGIN TEST (%s): RESOURCE: %s %s', str_repeat('*', 100).PHP_EOL, __FUNCTION__, $resourceClass, PHP_EOL.str_repeat('*', 100)));

        $adminApiUser = $this->createAdminApiUser($this->getSystemApiUser());

        $tenantApiUser = $this->createTenantApiUser($adminApiUser, [], 'Admin user creates a tenant user as the subject for testing permission access.');
        $vendorApiUser = $this->createVendorApiUser($this->createVendorResponse($adminApiUser), $adminApiUser, [], 'Admin user creates a vendor user as the subject for testing permission access.');

        $noAccessPermissionSet = ResourcePermissionChecker::createResourcePermissionSet();

        ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $noAccessPermissionSet);

        $this->debug('Test user role permissions');
        foreach([$tenantApiUser, $vendorApiUser] as $apiUser) {
            $allRoles = array_merge(ResourcePermissionChecker::getUserRoles($apiUser->getClass()), ResourcePermissionChecker::getActionRoles($resourceClass));
            foreach($allRoles as $role) {
                $apiUser->setRoles([$role]);
                if($diff = $apiUser->diff()) {
                    $adminApiUser
                    ->put($apiUser->getClass(), $apiUser->getId(), $diff)
                    ->assert()
                    ->log(sprintf('Admin user changes %s\'s role to %s', $this->getShortName($apiUser->getClass()), implode(', ', $apiUser->getRoles())));
                }
                else {
                    $this->addToTotalLogCount(1);
                }

                if($this->_testUserPermission($apiUser, $resource, 'User Role Permission')) {
                    ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $noAccessPermissionSet);
                }
            }
            $apiUser->setRoles([ResourcePermissionChecker::getUserRole($apiUser->getClass(), 'user')]);
            if($diff = $apiUser->diff()) {
                // Not sure whether required.
                $adminApiUser->authenticate();
                $adminApiUser
                ->put($apiUser->getClass(), $apiUser->getId(), $diff)
                ->assert()
                ->log(sprintf('Admin user changes %s\'s role to no access.', $this->getShortName($apiUser->getClass())));
            }
            else {
                $this->addToTotalLogCount(1);
            }
        }

        $this->debug('Test User ACL permissions');
        $userPermissionSets = $this->getAclPermissionSets(true);
        // Testing.  REMOVE!!!!!!!!!!!!
        //$userPermissionSets = [$userPermissionSets[3]];
        foreach($userPermissionSets as $permissionSet) {
            $resourceAclTracker->setPermissionSet($permissionSet);

            $adminApiUser
            ->customRequest('put', $resourceAclUri, $resourceAclTracker->normalize())
            ->assert()
            ->log(sprintf('Admin user changes %s\'s ACL policy to %s.', $this->getShortName($resourceClass), $permissionSet->toCrudString(true)));

            if($this->_testUserPermission($tenantApiUser, $resource, 'Tenant User ACL Permission')) {
                ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $permissionSet);
            }
            if($this->_testUserPermission($vendorApiUser, $resource, 'Vendor User ACL Permission')) {
                ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $permissionSet);
            }
        }

        $resourceAclTracker->setPermissionSet($noAccessPermissionSet); 
        if($diff = $resourceAclTracker->normalize()) {
            $adminApiUser
            ->customRequest('put', $resourceAclUri, $resourceAclTracker->normalize())
            ->assert()
            ->log(sprintf('Admin user changes %s\'s permission to no access.', $this->getShortName($resourceClass)));
        }
        else {
            $this->addToTotalLogCount(1);
        }

        // Each element is an array ['memberTracker'=>XXX, 'memberPath'=>xxx]
        $members = [];
        foreach([$tenantApiUser, $vendorApiUser] as $apiUser) {
            $memberPath = sprintf('%s/users/%s/resourceMember', $resourceUri, $apiUser->getId());
            $memberTracker = $this->createAclMemberTracker(
                $adminApiUser
                ->customRequest('post', $memberPath, [], ['populateBody'=>false])
                ->assert()
                ->log(sprintf('Admin user adds %s as a member to %s.', $this->getShortName($apiUser->getClass()), $this->getShortName($resourceClass)))
                ->toEntity()
            );
            $resourceAclTracker->addMember($memberTracker->getEntity());
            $members[$apiUser->getClass()] = ['memberTracker'=>$memberTracker, 'memberPath'=>$memberPath];
        }

        $this->debug('Test Member Role permissions');
        foreach([$tenantApiUser, $vendorApiUser] as $apiUser) {
            $memberTracker = $members[$apiUser->getClass()]['memberTracker'];
            $memberPath = $members[$apiUser->getClass()]['memberPath'];
            foreach($allRoles as $role) {
                $memberTracker->setRoles([$role]);
                $adminApiUser
                ->customRequest('put', $memberPath, $memberTracker->normalize())
                ->assert()
                ->log(sprintf('Admin user changes %s\'s member role to %s', $this->getShortName($apiUser->getClass()), $role));

                if($this->_testUserPermission($apiUser, $resource, 'Member Role Permission')) {
                    ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $noAccessPermissionSet);
                }
            }
        }

        $this->debug('Test Member ACL permissions');
        $memberPermissionSets = $this->getAclPermissionSets(false);
        $memberPermissions = $this->getAclPermissions();
        // Testing.  REMOVE!!!!!!!!!!!!
        //$memberPermissionSets = [$memberPermissionSets[3]];
        //$memberPermissions = [$memberPermissions[3]];
        foreach($memberPermissionSets as $i=>$permissionSet) {
            $resourceAclTracker->setPermissionSet($permissionSet);
            $adminApiUser
            ->customRequest('put', $resourceAclUri, $resourceAclTracker->normalize())
            ->assertStatusCode()
            ->log(sprintf('Admin user updated %s resource ACL permission to %s.', $resourceClass, $resourceAclTracker->getPermissionSet()->toCrudString()));
            foreach($memberPermissions as $j=>$memberPermission) {
                $this->debug(sprintf('Member Loop %d of %d | subloop %d of %d', $i, count($memberPermissionSets), $j, count($memberPermissions)));
                foreach([$tenantApiUser, $vendorApiUser] as $apiUser) {
                    $memberTracker = $members[$apiUser->getClass()]['memberTracker'];
                    $memberPath = $members[$apiUser->getClass()]['memberPath'];
                    $memberTracker->setRoles([]);
                    $memberTracker->setPermission($memberPermission);
                    $adminApiUser
                    ->customRequest('put', $memberPath, $memberTracker->normalize())
                    ->assert()
                    ->log(sprintf('Admin user changes %s\'s member permission to %s', $this->getShortName($apiUser->getClass()), $memberPermission->toCrudString(true)));

                    if($this->_testUserPermission($apiUser, $resource, 'Member ACL Permission')) {
                        ['resource' => $resource, 'resourceTracker' => $resourceTracker, 'resourceAclTracker' => $resourceAclTracker, 'resourceUri' => $resourceUri, 'resourceAclUri' => $resourceAclUri] = $this->createResource($adminApiUser, $resourceClass, $permissionSet);
                    }
                }
            }
        }
    }

    private function _testUserPermission(ApiUser $apiUser, HasResourceAclInterface $resource, string $type):bool
    {

        //$this->debug('CRUD TEST START - '.$this->getLogMessage('create, read, update, and delete', $apiUser, $resourceTracker));
        $this->debug(sprintf('CRUD TEST START - %s - %s %s', $type, $apiUser->getClass(), $resource::class));

        $apiUser->authenticate();    // Why is this required?

        $resourceClass = $resource::class;
        $user = $apiUser->getUser();
        $userClass = $user::class;

        $permissionChecker = $this->createResourcePermissionChecker($user, $resource);

        $apiUser
        ->post($resourceClass, $this->adjustPropertieValuesByUser($resource))
        ->setAuthorizationStatus($permissionChecker->getAuthorizationStatus('CREATE'))
        ->assert()
        ->log($this->getMsg($type, 'create', $userClass, $resourceClass));

        $apiUser
        ->getItem($resourceClass, $resource->getId())
        ->setAuthorizationStatus($permissionChecker->getAuthorizationStatus('READ'))
        ->assert()
        ->log($this->getMsg($type, 'read', $userClass, $resourceClass));

        $apiUser
        ->put($resourceClass, $resource->getId(), [], ['populateBody'=>true])
        ->setAuthorizationStatus($permissionChecker->getAuthorizationStatus('UPDATE'))
        ->assert()
        ->log($this->getMsg($type, 'update', $userClass, $resourceClass));

        $deleteResponse = $apiUser
        ->delete($resourceClass, $resource->getId())
        ->setAuthorizationStatus($permissionChecker->getAuthorizationStatus('DELETE'))
        ->assert()
        ->log($this->getMsg($type, 'delete', $userClass, $resourceClass));

        $this->debug('CRUD TEST COMPLETE.');
        return $deleteResponse->isSuccessful();
    }

    private function createResource(ApiUser $adminApiUser, string $resourceClass, AclPermissionSet $permissionSet):array
    {
        $resourceResponse = $adminApiUser
        ->post($resourceClass, $this->adjustPropertieValuesByAdmin($resourceClass, $adminApiUser))
        ->assert()
        ->log('Admin user creates test resource: '.$this->getShortName($resourceClass));

        $resourceArr = $resourceResponse->toArray();
        $resourceAclUri = $resourceArr['resourceAcl'];
        $resource = $resourceResponse->toEntity();
        $resourceAclTracker = $this->createAclTracker($resource->getResourceAcl());
        $resourceAclTracker->setPermissionSet($permissionSet);

        $adminApiUser
        ->customRequest('put', $resourceAclUri, $resourceAclTracker->normalize())
        ->assert()
        ->log(sprintf('Admin user sets %s\'s permission set to no access', $this->getShortName($resourceClass)));

        return [
            'resource' => $resource,
            'resourceTracker' => $this->createEntityTracker($resource),
            'resourceAclTracker' => $resourceAclTracker,
            'resourceUri' => $resourceArr['@id'],
            'resourceAclUri' => $resourceAclUri,
        ];
    }

    private function getMsg(string $type, string $action, string $userClass, string $resourceClass):string
    {
        return sprintf('%s test.  %s %s by %s', $type, $action, $this->getShortName($resourceClass), $this->getShortName($userClass));
    }

    private function adjustPropertieValuesByAdmin(string $resourceClass, ApiUser $adminApiUser):array
    {
        $properties = [];
        if($resourceClass === VendorUser::class) {
            $properties['organization'] = $this->apiRequestService->createLinkFromResponse($this->createVendorResponse($adminApiUser));
        }
        return $properties;
    }

    // Used to deal with unique constaints
    private function adjustPropertieValuesByUser(HasResourceAclInterface $resource):array
    {
        $properties = [];
        if(method_exists($resource,'setName')) {
            $properties['name'] = $resource->getName().'-'.time();
        }
        if($resource instanceof Project) {
            $properties['projectId'] = $resource->getProjectId().'-'.time();
        }
        if($resource instanceof VendorUser) {
            $properties['organization'] = $this->apiRequestService->createLink($resource->getOrganization());
        }
        return $properties;
    }

    protected static function getAclPermissionsArray():array
    {
        $permissions=[];
        for ($i = 0; $i < 1 << 2; $i++) {
            $permissions[] = ['read'=>$i>>0 & 1?'ALL':'NONE', 'update'=>$i>>1 & 1?'ALL':'NONE'];
        }
        return $permissions;
    }

    protected static function getTotalLogCount(): ?int
    {
        $classes = ResourcePermissionChecker::getResourceAclClasses();
        $userTypes = 2;
        $permissionSets = count(self::getAclPermissionSets(true));
        $permissions = count(self::getAclPermissions());
        $roleCount = 0;
        foreach($classes as $class) {
            $roleCount = $roleCount
            +count(ResourcePermissionChecker::getUserRoles(TenantUser::class))
            +count(ResourcePermissionChecker::getUserRoles(VendorUser::class))
            +$userTypes*count(ResourcePermissionChecker::getActionRoles($class));
        };
        $classes = count($classes);
        $roleCount = (int) ($roleCount/($classes*$userTypes));

        return
        $classes*$userTypes*(
            +2                      //Create tenant and AdminUser
            +1                      // Create User
            +1                      // Create Resource
            +1                      //Update resource valves
            +1                      //Update permissions
            + $roleCount * (
                +1                  //Update roles
                +4                  // Test
            )
            +1                      //Update permissions
            + $permissionSets * (   //User permission tests
                +1                  //Update permissions
                +4                  // Test
            )
            + 1                     //Reset permissions
            + 1                     //Add member
            + $roleCount * (
                +1                  //Update roles
                +4                  // Test
            )
            +1                      //Update permissions
            + $permissionSets * (   //Member permission tests
                + $permissions * (
                    +1              //Update permissions
                    +4               // Test
                )
            )
        );
    }

    public function getTestResourcePermissionData(): \Generator
    {
        foreach(ResourcePermissionChecker::getResourceAclClasses() as $resourceClass) {
            if($resourceClass !== Asset::class) {
                // Temp.  REMOVE this.
                //continue;
            }
            if($resourceClass === Archive::class) {
                // Currently do not have any Archive records to test.
                continue;
            }
            yield ['resourceClass' => $resourceClass];
        }
    }
}
