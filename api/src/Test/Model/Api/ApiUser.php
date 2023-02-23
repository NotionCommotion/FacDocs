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

namespace App\Test\Model\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Response;
use App\Entity\User\UserInterface;
use App\Test\Service\ApiRequestService;
use App\Test\Service\TestHelperService;
use App\Test\Service\TestLoggerService;
use App\Test\Service\EntityPersisterService;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;

class ApiUser
{
    private const CALL_API_CLIENT_METHODS = ['getClient', 'getApiRequestService', 'getApiTestCase', 'createApiUser'];
    private const CALL_API_CLIENT_ADD_USER_METHODS = ['uploadMockMedia', 'uploadInitialDocument', 'addMockMediaToDocument', 'request', 'sendFile', 'upload', 'download', 'dataRequest', 'getCollection', 'post', 'getItem', 'get', 'put', 'patch', 'delete', 'customRequest', 'customRequestWithClass'];
    private const CALL_API_SERVICE_METHODS = ['getAccessRoleService', 'getPath', 'createLink', 'addMessageLogItem', 'createEntityTracker', 'getEntityFromIdClass', 'getEntity'];
    private const CALL_ENTITY_TRACKER_METHODS = ['diff', 'normalize', 'getSerializer'];
    private const CALL_CLIENT_METHODS_RETURN_CLIENT = ['request', 'stream', 'getResponse', 'getKernelBrowser', 'getContainer', 'getCookieJar', 'getKernel', 'getProfile'];
    private const CALL_CLIENT_METHODS_RETURN_SELF = ['setDefaultOptions', 'enableProfiler', 'disableReboot', 'enableReboot', 'loginUser', 'withOptions'];

    private EntityTracker $entityTracker;
    private string $token;

    public function __construct(private ApiClient $apiClient, UserInterface $user, private string $password)
    {
        $this->entityTracker = $apiClient->getApiRequestService()->createEntityTracker($user);
        $this->token = $this->_getToken();
    }

    public function authenticate(): self
    {
        $this->token = $this->_getToken();
        return $this;
    }

    public function getClient(): Client
    {
        return $this->apiClient->getClient();
    }

    private function _getToken(): string
    {
        return $this->apiClient->getClient()
        ->request('POST', '/authentication_token', ['headers' => ['Content-Type' => 'application/json'],'json' => $this->getUser()->getLogon($this->password)])
        ->toArray()['token'];
    }

    public function __call($name, $arguments)
    {
        if(in_array($name, self::CALL_API_CLIENT_METHODS)) {
            return $this->apiClient->$name(...$arguments);
        }
        if(in_array($name, self::CALL_API_CLIENT_ADD_USER_METHODS)) {
            array_unshift($arguments, $this->getUser());
            return $this->apiClient->$name(...$arguments);
        }
        if(in_array($name, self::CALL_API_SERVICE_METHODS)) {
            return $this->apiClient->getApiRequestService()->$name(...$arguments);
        }
        if(in_array($name, self::CALL_ENTITY_TRACKER_METHODS)) {
            return $this->entityTracker->$name(...$arguments);
        }
        if(in_array($name, self::CALL_CLIENT_METHODS_RETURN_CLIENT)) {
            return $this->apiClient->getClient()->$name(...$arguments);
        }
        if(in_array($name, self::CALL_CLIENT_METHODS_RETURN_SELF)) {
            $this->apiClient->getClient()->$name(...$arguments);
            return $this;
        }
        if(is_callable([$this->getUser(), $name])) {
            // Standard Entity getters and setters.
            return $this->getUser()->$name(...$arguments);
        }
        trigger_error(sprintf('Call by %s::%s to undefined method %s::%s', debug_backtrace()[1]['class'], debug_backtrace()[1]['function'], __CLASS__, $name), E_USER_ERROR);
    }

    public function getUser(): UserInterface
    {
        return $this->entityTracker->getEntity();
    }

    public function hasUser(UserInterface $user): bool
    {
        return $this->getUser() === $user;
    }

    public function isUserGranted(string $role): bool
    {
        return $this->apiClient->getApiRequestService()->getAccessRoleService()->isUserGranted($this->getUser(), $role);
    }

    public function getReachableRoleNames(): array
    {
        return $this->apiClient->getApiRequestService()->getAccessRoleService()->getReachableRoleNames($this->getUser());
    }

    public function getUserPath(): string
    {
        $user = $this->getUser();
        return $this->apiClient->getApiRequestService()->getPath($user::class, $user->getId());
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
