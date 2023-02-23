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
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\HasDocumentAclInterface;
use ApiPlatform\Symfony\Bundle\Test\Response;
use App\Entity\PhpUnitTest\PhpUnitTestRecord;

class TestLogItem implements LogItemInterface
{
    //private AuthorizationStatusInterface $authorizationStatus;
    //private array $asserts=[];
    public function __construct(private string $message, private AbstractResponse $apiResponse, private ?string $notes=null, private array $extra=[])
    {
    }

    public function isSuccessful():bool
    {
        return $this->apiResponse->isSuccessful();
    }

    public function getResponse():Response
    {
        return $this->apiResponse->getResponse();
    }
    public function getApiRequest():ApiRequest
    {
        return $this->apiResponse->getApiRequest();
    }

    public function getMessage():?string
    {
        return $this->message;
    }

    public function getAuthorizationStatus():AuthorizationStatusInterface
    {
        return $this->apiResponse->getAuthorizationStatus();
    }

    public function getStatusCode():int
    {
        return $this->getResponse()->getStatusCode();
    }
    public function getAnticipatedStatusCode():int
    {
        return $this->getAuthorizationStatus()->getAnticipatedStatusCode();
    }

    public function getAsserts():array
    {
        return $this->apiResponse->getAsserts();
    }

    public function getData():array
    {
        $response = $this->getResponse();
        $apiRequest = $this->getApiRequest();
        return [
            'message' => $this->getMessage(),
            'access' => $this->getAuthorizationStatus()->getData(),
            'request' =>[
                'method' => $apiRequest->getMethod(),
                'path' => $apiRequest->getPath(),
                'body' => $apiRequest->getBody(),
                'parameters' => $apiRequest->getExtra(), //TBD whether correct.
                'headers' => $apiRequest->getHeaders(true),
            ],
            'response' => [
                'statusCode' => $response->getStatusCode(),
                'anticipatedStatusCode' => $this->getAnticipatedStatusCode(),
                'headers' => $this->apiResponse->getSafeHeaders(),
                'body' => $this->apiResponse->getSafeBody(),
            ],
            'asserts' => $this->getAsserts(),
        ];
    }

    public function createPhpUnitTestRecord():PhpUnitTestRecord
    {
        $data = $this->getData();
        return (new PhpUnitTestRecord)
        ->setMessage($data['message'])
        ->setAccess($data['access'])
        ->setRequest($data['request'])
        ->setResponse($data['response'])
        ->setAsserts($data['asserts']);
    }

    public function getDebugMessage():string
    {
        $msg = [];
        $msg[] = $this->message;
        $apiRequest = $this->getApiRequest();
        $response = $this->getResponse();
        if($this->getAnticipatedStatusCode()) {
            $msg[] = $this->getAuthorizationStatus()->getDebugMessage();
        }
        $msg[] = sprintf('REQUEST: %s %s', $apiRequest->getMethod(), $apiRequest->getPath());
        $msg[] = json_encode($apiRequest->getData());
        $msg[] = sprintf('RESPONSE - Anticipated Status: %d Actual Status: %d', $this->getAnticipatedStatusCode(), $response->getStatusCode());
        $msg[] = json_encode($this->apiResponse->hasContent()?$response->toArray():$response->getInfo());
        return implode(PHP_EOL, $msg);
    }

    private function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    public function debug():array
    {
        return [
            'apiRequest' => $this->getApiRequest()->debug(),
            'apiResponse' => $this->apiResponse->debug(),
            'statusCode' => $this->getStatusCode(),
            'anticipatedStatusCode' => $this->getAnticipatedStatusCode(),
            'class' => $this::class
        ];
    }
}
