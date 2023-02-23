<?php

/*
* This file is part of the FacDocs project.
*
* (c) Michael Reed villascape@gmail.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace App\Tests;

use App\Test\Service\ApiRequestService;
use App\Test\Model\Api\ApiUser;
use App\Test\Model\Api\AssertMethods;
use App\Entity\Organization\Tenant;

trait SystemUserTrait
{
    private static ApiUser $systemApiUser;

    public static function setUpBeforeClass(): void
    {
        $apiRequestService = static::getContainer()->get(ApiRequestService::class);
        $testHelperService = $apiRequestService->getTestHelperService();
        $systemUser = $testHelperService->getSystemUser();
        $systemApiUser = new ApiUser($apiRequestService, new AssertMethods, self::createClientWithCredentials($systemUser), $systemUser);
        $tenant = $systemApiUser->post(Tenant::class, ['name'=>'testing_tenant_'.time()], [], ['populateBody' => false])->assert(201)->log('Add new tenant by system user.')->toEntity();
        $systemUser->impersonate($tenant);
        self::authenticateClient($systemUser);
        //static::getContainer()->set('app.test.system.user', $systemApiUser);
        self::$systemApiUser = $systemApiUser;
    }

    public function getSystemApiUser():ApiUser
    {
        return self::$systemApiUser;
    }

    protected static function getApiRequestService(): ApiRequestService
    {
        return self::$systemApiUser->getApiRequestService(); //$apiRequestService;
    }
}