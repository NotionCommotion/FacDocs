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
use ApiPlatform\Symfony\Bundle\Test\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Ulid;
use App\Entity\User\UserInterface;
use App\Entity\Document\Document;
use App\Entity\Document\Media;
use App\Test\Service\ApiRequestService;
use App\Test\Service\TestLoggerService;
use App\Test\Model\AbstractTestCase;
use App\Test\Model\UploadedMockFile;
use App\Test\Model\FileTypes;

class ApiClient
{
    private const CHANGEABLE_PROPERTIES = ['supportedMediaTypes','customSpecifications','name','naicsCode','primarySpecification','phoneNumber','website','location','timezone','description','firstName','lastName','mobilePhoneNumber','directPhoneNumber','department','jobTitle','subject','status','allowedSpecification','permission','projectId','isActive','startAt','projectStage','defaultAsset','defaultSpecification','budget','projectTeamDescription','cost','decommissioned','mediaType','displayList','documentStage','specification','documentType'];

    private const DEFAULT_OPTIONS = [
        'populateBody' => false,
        'removeEmptyFromPopulateBody' => true,
        'removeEmpty' => false,
        'echoLog' => false,
        'debug' => false,
        'charset' => 'utf-8',
    ];
    private const DEFAULT_HEADERS = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/ld+json',
    ];

    private const DEFAULT_MOCK_MEDIA_SIZE = 10000;

    // User may pass $options such as ['debug'=>true, 'headers'=>[], 'extra'=>[]] where all are optional.
    private array $options=[];
    private array $headers=[];
    private \SplObjectStorage $apiUsers;
    private ?string $activeToken=null;

    public function __construct(private Client $client, private TestLoggerService $testLoggerService, private ApiRequestService $apiRequestService, private AbstractTestCase $apiTestCase, array $options=[])
    {
        $this->apiUsers = new \SplObjectStorage();
        $this->options = $this->getOptions(array_merge(self::DEFAULT_OPTIONS, $options));
        $this->headers = $this->getHeaders(array_merge(self::DEFAULT_HEADERS, $options['headers']??[]));
    }

    public function createApiUser(UserInterface $user, string $password): ApiUser
    {
        $apiUser = new ApiUser($this, $user, $password);
        $this->apiUsers[$user] = $apiUser;
        return $apiUser;
    }

    private function activate(UserInterface $user): self
    {
        $token = $this->apiUsers[$user]->getToken();
        if($this->activeToken !== $token) {
            $this->activeToken = $token;
            $this->client->setDefaultOptions($this->getCredentials($token));
        }
        return $this;
    }
    private function getCredentials(string $token): array
    {
        return ['headers' => ['authorization' => 'Bearer '.$token]];
    }

    // #### Raw requests ####
    // $options does not contain the header sub-array
    public function request(UserInterface $user, string $method, string $path, array $body=[], array $extra=[], array $headers=[], array $options=[]):Response
    {
        if($tenant = $user->getTenant()) {
            $headers['uuid'] = $tenant->getId();
        }
        return $this->dataRequest($user, $method, $path, $this->getData($body, $extra, $headers), $options);
    }
    public function sendFile(UserInterface $user, string $path, UploadedFile $file, array $parameters = [], array $extra=[], array $headers=[], array $options=[]):Response
    {
        $extra = ['files' => ['file' => $file]];
        if($parameters) {
            $extra['parameters'] = $parameters;
        }
        if($tenant = $user->getTenant()) {
            $headers['uuid'] = $tenant->getId();
        }
        return $this->dataRequest($user, 'POST', $path, $this->getData([], $extra, array_merge(['Content-Type' => 'multipart/form-data'], $headers)), $options);
    }

    public function dataRequest(UserInterface $user, string $method, string $path, array $data, array $options):Response
    {
        $options = $this->getOptions($options);
        if($options['removeEmpty'] || ($options['populateBody'] && $options['removeEmptyFromPopulateBody'])) {
            $data['json'] = $this->removeEmptyValues($data['json']); 
            $data = array_filter($data);
        }
        $this->activate($user);
        if($options['debug']) $this->echoRequest($user, $method, $path, $data);
        $response = $this->client->request(strtoupper($method), $path, $data);
        if($options['debug']) $this->echoResponse($response);
        return $response;
    }

    // #### Creating Entity Requests ####
    private function getCollectionRequest(UserInterface $user, string $class, array $options=[]):ApiRequest
    {
        return $this->entityRequest($user, 'GET', $class, null, [], $options);
    }
    private function postRequest(UserInterface $user, string $class, array $body=[], array $options=[]):ApiRequest
    {
        return $this->entityRequest($user, 'POST', $class, null, $body, array_merge(['populateBody' => true], $options));
    }
    private function getItemRequest(UserInterface $user, string $class, mixed $id, array $options=[]):ApiRequest
    {
        return $this->entityRequest($user, 'GET', $class, $id, [], $options);
    }
    private function putRequest(UserInterface $user, string $class, mixed $id, array $body=[], array $options=[]):ApiRequest
    {
        return $this->entityRequest($user, 'PUT', $class, $id, $body, $options);
    }
    private function patchRequest(UserInterface $user, string $class, mixed $id, array $body=[], array $options=[]):ApiRequest
    {
        $options['headers']['Content-Type'] = $options['headers']['Content-Type']??'application/merge-patch+json';
        return $this->entityRequest($user, 'PATCH', $class, $id, $body, $options);
    }
    private function deleteRequest(UserInterface $user, string $class, mixed $id, array $options=[]):ApiRequest
    {
        return $this->entityRequest($user, 'DELETE', $class, $id, [], $options);
    }
    private function entityRequest(UserInterface $user, string $method, string $class, mixed $id, array $body, array $options):ApiRequest
    {
        if($this->getOption('populateBody', $options)) {
            $body = array_merge($this->apiRequestService->getBody($class, $user->getTenant()), array_diff_key($body, array_flip(self::CHANGEABLE_PROPERTIES)));
        }
        return $this->apiRequest($user, $method, $this->apiRequestService->getPath($class, $id), $body, [], [], $options);
    }

    // #### Creating Media Requests ####
    private function downloadRequest(UserInterface $user, string $path, array $options):ApiRequest
    {
        return $this->apiRequest($user, 'GET', $path, [], [], [], $options);
    }
    private function uploadRequest(UserInterface $user, string $path, UploadedFile $file, array $options):ApiRequest
    {
        $extra = ['files' => ['file' => $file]];
        return $this->apiRequest($user, 'POST', $path, [], $extra, ['Content-Type' => 'multipart/form-data'], $options);
    }

    // Tenant will only be NULL for creating a new tenant using Post, for all tenant getCollection() and potentially for getItem().
    private function apiRequest(UserInterface $user, string $method, string $path, array $body=[], array $extra=[], array $headers=[], array $options=[]):ApiRequest
    {
        if(isset($options['headers'])) {
            $extra['headers'] = $parameters;
            unset($options['headers']);
        }
        if(isset($options['extra'])) {
            $extra = array_merge($extra, $options['extra']);
            unset($options['extra']);
        }
        if(isset($options['parameters'])) {
            $extra['parameters'] = $parameters;
            unset($options['parameters']);
        }
        if($tenant = $user->getTenant()) {
            $headers['uuid'] = $tenant->getId();
        }
        return new ApiRequest($method, $path, $body, $extra, $this->getHeaders($headers), $this->getOption('charset', $options));
    }

    // #### Primary methods which return a Response ####
    // Entity responses
    public function getCollection(UserInterface $user, string $class, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->getCollectionRequest($user, $class, $options), $user, $options, $class, $authorizationStatus);
    }
    public function post(UserInterface $user, string $class, array $body=[], array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->postRequest($user, $class, $body, $options), $user, $options, $class, $authorizationStatus);
    }
    public function getItem(UserInterface $user, string $class, mixed $id, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->getItemRequest($user, $class, $id, $options), $user, $options, $class, $authorizationStatus);
    }
    public function get(UserInterface $user, string $class, mixed $id=null, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse(is_null($id)?$this->getCollectionRequest($user, $class, $options):$this->getItemRequest($user, $class, $id, $options), $user, $options, $class, $authorizationStatus);
    }
    public function put(UserInterface $user, string $class, mixed $id, array $body=[], array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->putRequest($user, $class, $id, $body, $options), $user, $options, $class, $authorizationStatus);
    }
    public function patch(UserInterface $user, string $class, mixed $id, array $body=[], array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->patchRequest($user, $class, $id, $body, $options), $user, $options, $class, $authorizationStatus);
    }
    public function delete(UserInterface $user, string $class, mixed $id, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->deleteRequest($user, $class, $id, $options), $user, $options, $class, $authorizationStatus);
    }

    // custom EntityResponse
    public function customRequest(UserInterface $user, string $method, string $path, array $body=[], array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->customRequestWithClass($user, $method, $path, $this->apiRequestService->getClass($path), $body, $options, $authorizationStatus);
    }
    public function customRequestWithClass(UserInterface $user, string $method, string $path, string $class, array $body=[], array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        $apiRequest = $this->apiRequest($user, $method, $path, $body, [], [], $options, $class, $this->apiRequestService->getIdsFromPath($path));
        return $this->createEntityResponse($apiRequest, $user, $options, $class, $authorizationStatus);
    }

    public function upload(UserInterface $user, string $path, UploadedFile $file, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->createEntityResponse($this->uploadRequest($user, $path, $file, $options), $user, $options, Media::class, $authorizationStatus);
        //return $this->createUploadResponse($this->uploadRequest($user, $path, $file, $options), $user, $file, $options);
    }

    // DownloadResponse
    public function download(UserInterface $user, string $path, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):DownloadResponse
    {
        return $this->createDownloadResponse($this->downloadRequest($user, $path, $options), $user, $options, $authorizationStatus);
    }

    // ### Create Responses
    private function createEntityResponse(ApiRequest $apiRequest, UserInterface $user, array $options, string $class, ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        $response = $this->createResponse($apiRequest, $user, $options);
        if(!$authorizationStatus) {
            $authorizationStatus = $this->createDefaultAuthorizationStatus($response, $apiRequest, $user, $class);
        }
        return new EntityResponse($response, $apiRequest, $authorizationStatus, $this->apiTestCase, $this->testLoggerService, $this->apiRequestService->getEntityManager(), $class, $this->apiRequestService->getIdentifier($class), $this->getOption('echoLog', $options));
    }
    private function createDownloadResponse(ApiRequest $apiRequest, UserInterface $user, array $options, ?AuthorizationStatusInterface $authorizationStatus=null):DownloadResponse
    {
        $response = $this->createResponse($apiRequest, $user, $options);
        if(!$authorizationStatus) {
            $authorizationStatus = $this->createDefaultAuthorizationStatus($response, $apiRequest, $user, Media::class);
        }
        return new DownloadResponse($response, $apiRequest, $authorizationStatus, $this->apiTestCase, $this->testLoggerService, $this->getOption('echoLog', $options));
    }
    private function createResponse(ApiRequest $apiRequest, UserInterface $user, array $options):Response
    {
        $data = $this->getData($apiRequest->getBody(), $apiRequest->getExtra(), $apiRequest->getHeaders());
        return $this->dataRequest($user, $apiRequest->getMethod(), $apiRequest->getPath(), $data, $options);
    }
    private function createDefaultAuthorizationStatus(Response $response, ApiRequest $apiRequest, UserInterface $user, string $class):SimpleAuthorizationStatus
    {
        if($isSuccessful = $this->isSuccessful($response)) {
            try {
                $identifiers = $this->apiRequestService->getIdentifier($class);
            }
            catch(\Exception $e) {
                $identifiers = ['id'];
            }
            if($response->getContent()) {
                $id = array_intersect_key($response->toArray(), array_flip($identifiers));
                if(count($id)===1) {
                    $id=reset($id);
                }
            }
            else {
                $id = $this->apiRequestService->getIdsFromPath($apiRequest->getPath());
            }
        }
        return new SimpleAuthorizationStatus($user, $apiRequest->getAnticipatedStatusCode(), $isSuccessful, $class, $id??null);
    }

    // #### File helper methods ####
    public function uploadInitialDocument(UserInterface $user, string $resourceUri, array $options=[], array $body=[], ?AuthorizationStatusInterface $authorizationStatus=null, mixed $anticipatedStatusCode=201, string $log='Upload media.'):EntityResponse
    {
        // $anticipatedStatusCode and $log pertains to media and not document.
        // This is used for just testing documents and not media.  If testing media, use other method.

        $mediaResponse = $this->uploadMockMedia($user)->assert($anticipatedStatusCode)->log($log);

        $this->delayBeforeCreatingDocument($options);

        return $this->post($user, Document::class, array_merge(['media'=>$mediaResponse->toArray()['@id'], 'resource'=>$resourceUri], $body), array_diff_key($options, ['sleep'=>null]), $authorizationStatus);
    }

    public function addMockMediaToDocument(UserInterface $user, string $documentUri, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null, int $anticipatedStatusCode=201, string $log='Upload media to be used for the next document request.'):EntityResponse
    {
        // $anticipatedStatusCode and $log pertains to media and not document.
        // This is used for just testing documents and not media.  If testing media, use other method.

        $mediaResponse = $this->uploadMockMedia($user)->assert($anticipatedStatusCode)->log($log);

        $this->delayBeforeCreatingDocument($options);

        return $this->customRequestWithClass($user, 'post', $documentUri.$mediaResponse->toArray()['@id'], Media::class, [], array_diff_key($options, ['sleep'=>null]), $authorizationStatus);
    }

    private function delayBeforeCreatingDocument(array $options):self
    {
        if($options['sleep']??false) {
            $t0=microtime(true);
            sleep($options['sleep']);
            $log = sprintf('%s. Before creating document, wait for %d seconds after creating media.', $log, (microtime(true) - $t0)*1000);
        }
        return $this;
    }

    private function uploadMockMedia(UserInterface $user, array $options=[], ?AuthorizationStatusInterface $authorizationStatus=null):EntityResponse
    {
        return $this->upload($user, '/media', UploadedMockFile::create(new FileTypes\Text($options['size']??self::DEFAULT_MOCK_MEDIA_SIZE)), array_diff_key($options, ['size'=>null]), $authorizationStatus);
    }

    // #### Support for getting data, headers, etc ####
    private function getData(array $body, array $extra, array $headers):array
    {
        return [
            'headers' => $this->getHeaders($headers),
            'json'=>$body,
            'extra'=>$extra
        ];
    }

    private function getOption(string $name, array $options):mixed
    {
        $options = $this->getOptions($options);
        if(!isset($options[$name])) {
            throw new \Exception(sprintf('Invalid option: %s.', $name));
        }
        return $options[$name];
    }
    private function getOptions(array $options):array
    {
        unset($options['headers'], $options['extra']);
        if($err = array_diff_key($options, self::DEFAULT_OPTIONS)) {
            throw new \Exception(sprintf('Invalid options: %s.', implode(', ', array_keys($err))));
        }
        return array_merge($this->options, $options);
    }
    private function getHeaders(array $headers, bool $subArray=false):array
    {
        if($subArray) {
            $headers = $headers['headers']??[];
        }
        if(!$this->isAssoc($headers)) {
            throw new \Exception(sprintf('Headers must be an associated array.  %s given.', json_encode($headers)));
        }
        return array_merge($this->headers, $headers);
    }

    // #### Debug Logging ####
    private function echoRequest(UserInterface $user, string $method, string $path, array $data):void
    {
        printf(PHP_EOL.PHP_EOL.'REQUEST: %s %s User ID: %s/%s, User Roles: %s%s%s'.PHP_EOL, $method, $path, $user->getId()->toBase32(), $user->getId()->toRfc4122(), implode(', ', $user->getRoles()), PHP_EOL, json_encode($data));
    }
    private function echoResponse(Response $response):void
    {
        $status = $response->getStatusCode();
        if($this->isSuccessful($response)) {
            printf(PHP_EOL.'RESPONSE: Status: %d:%s%s'.PHP_EOL.PHP_EOL, $status, PHP_EOL, $response->getContent());
        }
        else{
            printf(PHP_EOL.'RESPONSE Error: StatusCode: %d%s%s'.PHP_EOL, $status, PHP_EOL, json_encode($response->getInfo()));
        }
    }
    private function isSuccessful(Response $response):bool
    {
        $status = $response->getStatusCode();
        return (200 <= $status) && ($status < 300);
    }

    // #### General Support Methods ####
    private function removeEmptyValues(array $arr):array
    {
        return array_filter($arr, function($prop){return !is_null($prop)&&!(is_array($prop)&&empty($prop));});
    }

    private function isAssoc(array $a)
    {
        return count(array_filter(array_keys($a), 'is_string')) === count($a);
    }

    // #### Other Getters ####
    public function getClient():Client
    {
        return $this->client;
    }
    public function getApiRequestService():ApiRequestService
    {
        return $this->apiRequestService;
    }
    public function getApiTestCase():AbstractTestCase
    {
        return $this->apiTestCase;
    }
    public function getTestLoggerService():TestLoggerService
    {
        return $this->testLoggerService;
    }
}
