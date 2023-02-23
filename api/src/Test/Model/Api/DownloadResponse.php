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

use App\Test\Service\TestLoggerService;
use ApiPlatform\Symfony\Bundle\Test\Response;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\UserInterface;

class DownloadResponse extends AbstractResponse implements ResponseInterface
{
    public function __construct(Response $response, ApiRequest $apiRequest, AuthorizationStatusInterface $authorizationStatus, ApiTestCase $apiTestCase, TestLoggerService $testLoggerService, bool $echoLog = false)
    {
        if(!$apiRequest->isGet()) {
            throw new \Exception('Method must be GET item. '.json_encode($apiRequest->debug()));
        }
        parent::__construct($response, $apiRequest, $authorizationStatus, $apiTestCase, $testLoggerService, $echoLog);
    }

    protected function _assert(AssertEnum ...$assertEnum):self
    {
        // $subjectApiUser->downloadDocumentMedia($documentUrl, $statusRead, 'download documentMedia', ['size'=>10000, 'statusCode'=>$statusRead->getAnticipatedStatusCode()]);
        $this->assertResponseStatusCodeSame($this->getAnticipatedStatusCode());

        if($this->isSuccessful()) {
            // More validation
        }
        return $this;
    }

    public function assertSameSize(int $anticipatedSize):self
    {
        if($this->isSuccessful()) {
            $this->assertEquals($anticipatedSize, $this->getContentLength($this->getResponse()));
        }
        return $this;
    }

    public function assertSameMediaType(string $anticipatedMediaType):self
    {
        if($this->isSuccessful()) {
            $this->assertResponseHasHeader('content-type', $anticipatedMediaType);
        }
        return $this;
    }
}
