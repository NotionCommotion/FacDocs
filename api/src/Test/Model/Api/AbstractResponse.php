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

abstract class AbstractResponse implements ResponseInterface
{
    private const SUPPORTTED_ASSERT_METHODS = ['any', 'never', 'atLeast', 'atLeastOnce', 'once', 'exactly', 'atMost', 'at', 'returnValue', 'returnValueMap', 'returnArgument', 'returnCallback', 'returnSelf', 'throwException', 'onConsecutiveCalls', '__construct', 'setUpBeforeClass', 'tearDownAfterClass', 'toString', 'count', 'getActualOutputForAssertion', 'expectOutputRegex', 'expectOutputString', 'expectException', 'expectExceptionCode', 'expectExceptionMessage', 'expectExceptionMessageMatches', 'expectExceptionObject', 'expectNotToPerformAssertions', 'expectDeprecation', 'expectDeprecationMessage', 'expectDeprecationMessageMatches', 'expectNotice', 'expectNoticeMessage', 'expectNoticeMessageMatches', 'expectWarning', 'expectWarningMessage', 'expectWarningMessageMatches', 'expectError', 'expectErrorMessage', 'expectErrorMessageMatches', 'getStatus', 'markAsRisky', 'getStatusMessage', 'hasFailed', 'run', 'getMockBuilder', 'registerComparator', 'doubledTypes', 'getGroups', 'setGroups', 'getName', 'getSize', 'hasSize', 'isSmall', 'isMedium', 'isLarge', 'getActualOutput', 'hasOutput', 'doesNotPerformAssertions', 'hasExpectationOnOutput', 'getExpectedException', 'getExpectedExceptionCode', 'getExpectedExceptionMessage', 'getExpectedExceptionMessageRegExp', 'setRegisterMockObjectsFromTestArgumentsRecursively', 'runBare', 'setName', 'setDependencies', 'setDependencyInput', 'setBeStrictAboutChangesToGlobalState', 'setBackupGlobals', 'setBackupStaticAttributes', 'setRunTestInSeparateProcess', 'setRunClassInSeparateProcess', 'setPreserveGlobalState', 'setInIsolation', 'isInIsolation', 'getResult', 'setResult', 'setOutputCallback', 'getTestResultObject', 'setTestResultObject', 'registerMockObject', 'addToAssertionCount', 'getNumAssertions', 'usesDataProvider', 'dataName', 'getDataSetAsString', 'getProvidedData', 'addWarning', 'sortId', 'provides', 'requires', 'assertArrayHasKey', 'assertArrayNotHasKey', 'assertContains', 'assertContainsEquals', 'assertNotContains', 'assertNotContainsEquals', 'assertContainsOnly', 'assertContainsOnlyInstancesOf', 'assertNotContainsOnly', 'assertCount', 'assertNotCount', 'assertEquals', 'assertEqualsCanonicalizing', 'assertEqualsIgnoringCase', 'assertEqualsWithDelta', 'assertNotEquals', 'assertNotEqualsCanonicalizing', 'assertNotEqualsIgnoringCase', 'assertNotEqualsWithDelta', 'assertObjectEquals', 'assertEmpty', 'assertNotEmpty', 'assertGreaterThan', 'assertGreaterThanOrEqual', 'assertLessThan', 'assertLessThanOrEqual', 'assertFileEquals', 'assertFileEqualsCanonicalizing', 'assertFileEqualsIgnoringCase', 'assertFileNotEquals', 'assertFileNotEqualsCanonicalizing', 'assertFileNotEqualsIgnoringCase', 'assertStringEqualsFile', 'assertStringEqualsFileCanonicalizing', 'assertStringEqualsFileIgnoringCase', 'assertStringNotEqualsFile', 'assertStringNotEqualsFileCanonicalizing', 'assertStringNotEqualsFileIgnoringCase', 'assertIsReadable', 'assertIsNotReadable', 'assertNotIsReadable', 'assertIsWritable', 'assertIsNotWritable', 'assertNotIsWritable', 'assertDirectoryExists', 'assertDirectoryDoesNotExist', 'assertDirectoryNotExists', 'assertDirectoryIsReadable', 'assertDirectoryIsNotReadable', 'assertDirectoryNotIsReadable', 'assertDirectoryIsWritable', 'assertDirectoryIsNotWritable', 'assertDirectoryNotIsWritable', 'assertFileExists', 'assertFileDoesNotExist', 'assertFileNotExists', 'assertFileIsReadable', 'assertFileIsNotReadable', 'assertFileNotIsReadable', 'assertFileIsWritable', 'assertFileIsNotWritable', 'assertFileNotIsWritable', 'assertTrue', 'assertNotTrue', 'assertFalse', 'assertNotFalse', 'assertNull', 'assertNotNull', 'assertFinite', 'assertInfinite', 'assertNan', 'assertClassHasAttribute', 'assertClassNotHasAttribute', 'assertClassHasStaticAttribute', 'assertClassNotHasStaticAttribute', 'assertObjectHasAttribute', 'assertObjectNotHasAttribute', 'assertSame', 'assertNotSame', 'assertInstanceOf', 'assertNotInstanceOf', 'assertIsArray', 'assertIsBool', 'assertIsFloat', 'assertIsInt', 'assertIsNumeric', 'assertIsObject', 'assertIsResource', 'assertIsClosedResource', 'assertIsString', 'assertIsScalar', 'assertIsCallable', 'assertIsIterable', 'assertIsNotArray', 'assertIsNotBool', 'assertIsNotFloat', 'assertIsNotInt', 'assertIsNotNumeric', 'assertIsNotObject', 'assertIsNotResource', 'assertIsNotClosedResource', 'assertIsNotString', 'assertIsNotScalar', 'assertIsNotCallable', 'assertIsNotIterable', 'assertMatchesRegularExpression', 'assertRegExp', 'assertDoesNotMatchRegularExpression', 'assertNotRegExp', 'assertSameSize', 'assertNotSameSize', 'assertStringMatchesFormat', 'assertStringNotMatchesFormat', 'assertStringMatchesFormatFile', 'assertStringNotMatchesFormatFile', 'assertStringStartsWith', 'assertStringStartsNotWith', 'assertStringContainsString', 'assertStringContainsStringIgnoringCase', 'assertStringNotContainsString', 'assertStringNotContainsStringIgnoringCase', 'assertStringEndsWith', 'assertStringEndsNotWith', 'assertXmlFileEqualsXmlFile', 'assertXmlFileNotEqualsXmlFile', 'assertXmlStringEqualsXmlFile', 'assertXmlStringNotEqualsXmlFile', 'assertXmlStringEqualsXmlString', 'assertXmlStringNotEqualsXmlString', 'assertEqualXMLStructure', 'assertThat', 'assertJson', 'assertJsonStringEqualsJsonString', 'assertJsonStringNotEqualsJsonString', 'assertJsonStringEqualsJsonFile', 'assertJsonStringNotEqualsJsonFile', 'assertJsonFileEqualsJsonFile', 'assertJsonFileNotEqualsJsonFile', 'logicalAnd', 'logicalOr', 'logicalNot', 'logicalXor', 'anything', 'isTrue', 'callback', 'isFalse', 'isJson', 'isNull', 'isFinite', 'isInfinite', 'isNan', 'containsEqual', 'containsIdentical', 'containsOnly', 'containsOnlyInstancesOf', 'arrayHasKey', 'equalTo', 'equalToCanonicalizing', 'equalToIgnoringCase', 'equalToWithDelta', 'isEmpty', 'isWritable', 'isReadable', 'directoryExists', 'fileExists', 'greaterThan', 'greaterThanOrEqual', 'classHasAttribute', 'classHasStaticAttribute', 'objectHasAttribute', 'identicalTo', 'isInstanceOf', 'isType', 'lessThan', 'lessThanOrEqual', 'matchesRegularExpression', 'matches', 'stringStartsWith', 'stringContains', 'stringEndsWith', 'countOf', 'objectEquals', 'fail', 'markTestIncomplete', 'markTestSkipped', 'getCount', 'resetCount', 'assertEmailCount', 'assertQueuedEmailCount', 'assertEmailIsQueued', 'assertEmailIsNotQueued', 'assertEmailAttachmentCount', 'assertEmailTextBodyContains', 'assertEmailTextBodyNotContains', 'assertEmailHtmlBodyContains', 'assertEmailHtmlBodyNotContains', 'assertEmailHasHeader', 'assertEmailNotHasHeader', 'assertEmailHeaderSame', 'assertEmailHeaderNotSame', 'assertEmailAddressContains', 'getMailerEvents', 'getMailerEvent', 'getMailerMessages', 'getMailerMessage', 'assertJsonContains', 'assertJsonEquals', 'assertArraySubset', 'assertMatchesJsonSchema', 'assertMatchesResourceCollectionJsonSchema', 'assertMatchesResourceItemJsonSchema', 'assertResponseIsSuccessful', 'assertResponseStatusCodeSame', 'assertResponseFormatSame', 'assertResponseRedirects', 'assertResponseHasHeader', 'assertResponseNotHasHeader', 'assertResponseHeaderSame', 'assertResponseHeaderNotSame', 'assertResponseHasCookie', 'assertResponseNotHasCookie', 'assertResponseCookieValueSame', 'assertResponseIsUnprocessable', 'assertBrowserHasCookie', 'assertBrowserNotHasCookie', 'assertBrowserCookieValueSame', 'assertRequestAttributeValueSame', 'assertRouteSame', 'assertThatForResponse', 'assertThatForClient'];
    private const NON_SUPPORTTED_METHODS = ['tearDown', 'createClient', 'findIriBy', 'getKernelClass', 'bootKernel', 'getContainer', 'createKernel', 'ensureKernelShutdown', 'setUp', 'assertPreConditions', 'assertPostConditions', 'runTest', 'iniSet', 'setLocale', 'createStub', 'createMock', 'createConfiguredMock', 'createPartialMock', 'createTestProxy', 'getMockClass', 'getMockForAbstractClass', 'getMockFromWsdl', 'getMockForTrait', 'getObjectForTrait', 'prophesize', 'createResult', 'onNotSuccessfulTest', 'recordDoubledType'];

    private ?int $anticipatedStatusCode=null;

    private bool $hasLogged=false;
    private array $asserts = [];

    public function __construct(private Response $response, private ApiRequest $apiRequest, private AuthorizationStatusInterface $authorizationStatus, private ApiTestCase $apiTestCase, private TestLoggerService $testLoggerService, private bool $echoLog = false)
    {
    }

    public function __call($name, $arguments)
    {
        //If any of the ApiTestCase non-public methods listed below are required, will need to add my own method to AbstractTest
        //getService, getTestUserContainer, getApiRequestService, queryResponse, denormalizeResponse, denormalizeArray, link, getObjectId, getPath, createClientWithCredentials, authenticate, getToken, getLogon, getPrototype, _, __, tearDown, createClient, findIriBy, getKernelClass, bootKernel, getContainer, createKernel, ensureKernelShutdown, assertPreConditions, assertPostConditions, runTest, iniSet, setLocale, createStub, createMock, createConfiguredMock, createPartialMock, createTestProxy, getMockClass, getMockForAbstractClass, getMockFromWsdl, getMockForTrait, getObjectForTrait, prophesize, createResult, onNotSuccessfulTest, recordDoubledType
        // Returns $this instead of null as these methods will really only be used to validate.
        if(!in_array($name, self::SUPPORTTED_ASSERT_METHODS)) {
            trigger_error(sprintf('Call by %s::%s to undefined method %s::%s', debug_backtrace()[1]['class'], debug_backtrace()[1]['function'], __CLASS__, $name), E_USER_ERROR);
        }
        if($this->hasLogged) {
            throw new \Exception('Asserts may not be made after logging');
        }
        $this->asserts[] = ['name' => $name, 'arguments' => $this->getMethodArguements(ApiTestCase::class, $name, $arguments)];
        $this->apiTestCase->$name(...$arguments);
        return $this;
    }
    private function getMethodArguementNames(string $class, string $method):array
    {
        return array_map(function( $item ){return $item->getName();}, (new \ReflectionMethod($class, $method))->getParameters());
    }
    private function getMethodArguements(string $class, string $method, array $arguments):array
    {
        return array_combine(array_slice($this->getMethodArguementNames($class, $method), 0, count($arguments), true), $arguments);
    }


    public function getAuthorizationStatus():AuthorizationStatusInterface
    {
        return $this->authorizationStatus;
    }

    public function setAuthorizationStatus(AuthorizationStatusInterface $authorizationStatus):self
    {
        if($this->asserts) {
            throw new \Exception('Cannot change authorizationstatus after asserting.');
        }
        $this->authorizationStatus = $authorizationStatus;
        return $this;
    }

    // Response methods
    public function getResponse():Response
    {
        return $this->response;
    }

    public function getHeaders():array
    {
        return $this->response->getHeaders();
    }

    public function getInfo():array
    {
        return $this->response->getInfo();
    }

    public function getStatusCode():int
    {
        return $this->response->getStatusCode();
    }

    // Request methods
    public function getApiRequest():ApiRequest
    {
        return $this->apiRequest;
    }

    public function getPath():string
    {
        return $this->apiRequest->getPath();
    }

    public function getExtra():array
    {
        return $this->apiRequest->getExtra();
    }

    public function getMethod():string
    {
        return $this->apiRequest->getMethod();
    }

    public function getRequestHeaders():array
    {
        return $this->apiRequest->getHeaders();
    }

    public function getExpectedContentType():string
    {
        return $this->apiRequest->getExpectedContentType();
    }

    // Other methods
    public function getAsserts():array
    {
        return $this->asserts;
    }

    public function isSuccessful():bool
    {
        $status = $this->response->getStatusCode();
        return (200 <= $status) && ($status < 300);
    }

    protected function getData():array
    {
        return ['headers' => $this->headers, 'json'=>[], 'extra'=>$this->extra];
    }

    protected function getHeader(string $name, Response $response):array
    {
        return $response->getHeaders()[$name];
    }
    protected function getContentLength(Response $response):int
    {
        return (int) $this->getHeader('content-length', $response)[0];
    }
    protected function getContentType(Response $response):string
    {
        return $this->getHeader('content-type', $response)[0];
    }
    protected function getContentDisposition(Response $response):string
    {
        return $this->getHeader('content-disposition', $response)[0];
    }
    protected function getApiTestCase():ApiTestCase
    {
        return $this->apiTestCase;
    }

    public function hasContent():bool
    {
        return $this->isSuccessful() && !empty($this->response->getContent());
    }

    public function getSafeHeaders():array
    {
        return $this->isSuccessful()?$this->response->getHeaders():[];//$this->response()->getBrowserKitResponse()->getHeaders();
    }

    public function getSafeBody():?array
    {
        return $this->hasContent()?$this->response->toArray():null;//[$this->response->getContent()];
    }

    public function getAnticipatedStatusCode():?int
    {
        return $this->anticipatedStatusCode;
    }
    public function setAnticipatedStatusCode(?int ...$anticipatedStatusCode):self
    {
        $anticipatedStatusCode = array_values(array_filter($anticipatedStatusCode, function(?int $id){return !is_null($id);}));
        if(count($anticipatedStatusCode)>1) {
            $actualStatusCode = $this->getStatusCode();
            $anticipatedStatusCode = [in_array($actualStatusCode, $anticipatedStatusCode)?$actualStatusCode:null];
        }
        $this->anticipatedStatusCode = $anticipatedStatusCode[0]??($this->authorizationStatus?$this->authorizationStatus->getAnticipatedStatusCode():$this->apiRequest->getAnticipatedStatusCode());
        return $this;
    }
    protected function preErrorDebug(int $anticipatedStatusCode, bool $verbose=false):self
    {
        if($this->response->getStatusCode()!== $anticipatedStatusCode) {
            $this->testLoggerService->echoDebug('AbstractResponse::preErrorDebug()', $this);
            if($verbose) {
                printf('Anticipated Status Code: %s Actual Status Code: %s'.PHP_EOL, $anticipatedStatusCode, $this->response->getStatusCode());
                echo('AbstractResponse::debug(true)'.PHP_EOL);
                print_r($this->debug(false));
                if($content = @$this->response->getContent()) {
                    $json = @json_decode($content, true);
                    if(json_last_error() === JSON_ERROR_NONE) {
                        echo('Response::getContent())'.PHP_EOL);
                        print_r($json);
                        echo('AbstractResponse::debugResponse()'.PHP_EOL);
                        print_r($this->debugResponse());
                    }
                    else {
                        printf('INVALID JSON: %s'.PHP_EOL, $content);
                    }
                }
                else {
                    echo('BODY IS EMPTY'.PHP_EOL);
                }
            }
        }
        return $this;
    }

    public function assert(mixed $anticipatedStatusCode=null, AssertEnum ...$assertEnum):self
    {
        if($this->hasLogged) {
            throw new \Exception('Asserts may not be made after logging');
        }
        $this->setAnticipatedStatusCode(...(array) $anticipatedStatusCode);

        return $this
        ->preErrorDebug($this->anticipatedStatusCode)
        ->_assert(...$assertEnum);
    }

    public function assertStatusCode(mixed $anticipatedStatusCode=null):self
    {
        if($this->hasLogged) {
            throw new \Exception('Asserts may not be made after logging');
        }
        $this->setAnticipatedStatusCode(...(array) $anticipatedStatusCode);

        $this->preErrorDebug($this->anticipatedStatusCode);
        $this->assertResponseStatusCodeSame($this->getAnticipatedStatusCode());
        return $this;
    }

    // Override as necessary
    protected function _assert(AssertEnum ...$assertEnum):self
    {
        $this->assertResponseStatusCodeSame($this->getAnticipatedStatusCode());
        return $this;
    }

    public function log(string $message=null, ?string $notes=null, array $extra=[]):self
    {
        if(is_null($this->anticipatedStatusCode)) {
            throw new \Exception('anticipatedStatusCode must set before logging.');
        }
        if($this->hasLogged) {
            throw new \Exception('Log cannot be called twice');
        }
        $this->hasLogged = true;
        $logItem = $this->testLoggerService->addLogItem($message, $this, $notes, $extra);
        if($this->echoLog) {
            echo(PHP_EOL.'--- START LOG ---'.PHP_EOL);
            echo($logItem->getDebugMessage());
            echo(PHP_EOL.'--- END LOG ---'.PHP_EOL.PHP_EOL);
        }
        return $this;
    }

    public function echo():self
    {
        print_r($this->toArray());
        return $this;
    }

    public function debugResponse():?array
    {
        $anticipatedResponse = $this->apiRequest->getBody();
        $actualResponse = $this->getSafeBody();
        return ['anticipated'=>$anticipatedResponse, 'actual'=>$actualResponse, 'diff'=>['missing'=>$this->arrayRecursiveDiff($anticipatedResponse, $actualResponse), 'extra'=>$this->arrayRecursiveDiff($actualResponse, $anticipatedResponse)]];;
    }

    private function arrayRecursiveDiff($aArray1, $aArray2):array
    {
        $aReturn = [];

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    } 

    public function pretest(AuthorizationStatusInterface $authorizationStatus)
    {
        if($this->response->getStatusCode() !== $authorizationStatus->getAnticipatedStatusCode()) {
            print_r($authorizationStatus->debug());
        }
        return $this;
    }

    public function debug(bool $verbous=false):array
    {
        return ['apiRequest' => $this->apiRequest->debug($verbous), 'response'=>$this->response->getInfo(), 'authorizationStatus'=>$this->authorizationStatus->debug(), 'anticipatedStatusCode'=>$this->anticipatedStatusCode, 'class'=>$this::class];
    }
}
