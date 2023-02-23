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

trait ApiRequestServiceTrait
{
    private static ApiRequestService $apiRequestService;

    public static function setUpBeforeClass(): void
    {
        // Kludge.  See https://stackoverflow.com/questions/74788815/how-to-use-autowired-services-when-using-phpunit
        //static::getContainer()->set('app.test.api.request.service', static::getContainer()->get(ApiRequestService::class));
        self::$apiRequestService = static::getContainer()->get(ApiRequestService::class);
    }

    protected static function getApiRequestService(): ApiRequestService
    {
        return self::$apiRequestService;
    }
}