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

use App\Test\Model\Api\EntityContainer;
use App\Test\Service\ApiRequestService;
use App\Entity\User\TenantUser;
use App\Entity\Organization\Tenant;

trait EntityContainerTrait
{
    private static EntityContainer $entityContainer;
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$entityContainer = self::createEntityContainer();
    }
    private static function createEntityContainer():EntityContainer
    {
        self::addMessageLogItem('Create initial tenant.');
        $systemUser = self::getTestHelperService()->getTestingSystemUser('ROLE_SYSTEM_ADMIN');
        $systemUserRequester = self::getApiRequestService($systemUser);

        $tenant = $systemUserRequester->post(Tenant::class)->assert(201)->log('Add new tenant by system user.')->toEntity();

        $systemUser->impersonate($tenant);
        $systemUserRequester->setClient(self::createClientWithCredentials($systemUser));

        $rootUser = $systemUserRequester->post(TenantUser::class, ['roles'=>['ROLE_TENANT_SUPER'], 'password'=>$this->getPassword()])->assert(201)->log('Create root tenant user by system user.')->toEntity();

        return new EntityContainer($rootUser);
    }

    protected static function getRootUserRequestService(): ApiRequestService
    {
        return self::getApiRequestService(self::getEntityContainer()->getRootUser());
    }

    protected static function getEntityContainer(): EntityContainer
    {
        return self::$entityContainer;
    }
}