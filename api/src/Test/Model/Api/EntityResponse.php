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
use App\Entity\User\UserInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Specification\CustomSpecification;
use App\Entity\Project\Project;
use App\Entity\Document\Document;
use App\Entity\Acl\AclInterface;
use App\Entity\Acl\AclMemberInterface;
use App\Test\Service\TestLoggerService;
use ApiPlatform\Symfony\Bundle\Test\Response;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\EntityManagerInterface;

class EntityResponse extends AbstractResponse implements ResponseInterface
{
    private ?object $subjectEntity=null;
    private mixed $id;
    
    public function __construct(Response $response, ApiRequest $apiRequest, AuthorizationStatusInterface $authorizationStatus, ApiTestCase $apiTestCase, TestLoggerService $testLoggerService, private EntityManagerInterface $entityManager, private string $class, private array $identifier, bool $echoLog = false)
    {
        parent::__construct($response, $apiRequest, $authorizationStatus, $apiTestCase, $testLoggerService, $echoLog);
    }

    public function getClass():string
    {
        return $this->class;
    }
    public function getId():mixed
    {
        return $this->toEntity->getId();
    }

    public function getIdentifier():array
    {
        return $this->identifier;
    }

    protected function _assert(AssertEnum ...$assertEnum):self
    {
        //$iri = $this->findIriBy(Book::class, ['isbn' => '9781344037075']);
        //printf('%s successful: %s method: %s anticipatedStatusCode: %s actualStatusCode: %s testStatus: %s'.PHP_EOL, __FUNCTION__, $this->isSuccessful()?'y':'n', $this->apiRequest->getMethod(), $anticipatedStatusCode, $this->getStatusCode(), $apiTestCase->getStatus());
        //printf('isDelete: %s isUpdate: %s isPost: %s isGetItem: %s isGetCollection: %s'.PHP_EOL, $this->apiRequest->isDelete()?'y':'n', $this->apiRequest->isUpdate()?'y':'n', $this->apiRequest->isPost()?'y':'n', $this->apiRequest->isGetItem()?'y':'n', $this->apiRequest->isGetCollection()?'y':'n');

        $this->assertResponseStatusCodeSame($this->getAnticipatedStatusCode());

        $apiRequest = $this->getApiRequest();
        if($this->isSuccessful()) {
            $body = $this->removeUnsupportedProperties($apiRequest->getBody(), $this->class);
            switch(true) {
                case $apiRequest->isDelete():
                    /*
                    $this->assertNull(
                    // Through the container, you can access all your services from the tests, including the ORM, the mailer, remote API clients...
                    static::getContainer()->get('doctrine')->getRepository(Book::class)->findOneBy(['isbn' => '9781344037075'])
                    );
                    */
                    // Temp solution.
                    $this->isNull();

                    break;
                case $apiRequest->isUpdate():
                    /*
                    print_r($body);
                    print_r($this->getResponse()->toArray());
                    print_r($apiRequest->debug());
                    print_r($this->debug());
                    */
                    $this->assertResponseHeaderSame('content-type', $this->getExpectedContentType());
                    $this->assertJsonContains($body);
                    break;
                case $apiRequest->isPost():
                    $this->assertResponseHeaderSame('content-type', $this->getExpectedContentType());
                    $this->assertJsonContains($body);
                    break;
                case $apiRequest->isGetItem():
                    $this->assertResponseHeaderSame('content-type', $this->getExpectedContentType());
                    //$this->assertJsonContains([]);
                    break;
                case $apiRequest->isGetCollection():
                    $this->assertResponseHeaderSame('content-type', $this->getExpectedContentType());
                    // Verify counts
                    /*
                    $this->assertCount(30, $response->toArray()['hydra:member']);
                    $this->assertMatchesResourceCollectionJsonSchema(Book::class);
                    $this->assertNull(
                    // Through the container, you can access all your services from the tests, including the ORM, the mailer, remote API clients...
                    static::getContainer()->get('doctrine')->getRepository(Book::class)->findOneBy(['isbn' => '9781344037075'])
                    );
                    */
                    break;
                default:
                    throw new \Exception('Invalid method: '.$apiRequest->getMethod());
            }
        }
        return $this;
    }

    private function removeUnsupportedProperties(array $body, string $class):array
    {
        //$body = array_diff_key($body, array_flip(['password']));
        /*
        if(isset($body['primarySpecification'])) {
        // When making a request for specifications, either returns csi_specifications or custom_specifications, and validation fails.
        //str_replace('///specifications', '///csi_specifications', $body['primarySpecification']);
        //$body['primarySpecification'] = preg_replace('/\specifications\b/', 'csi_specifications', $body['primarySpecification']);
        //$body['primarySpecification'] = str_replace('specifications', 'csi_specifications', $body['primarySpecification']);
        }
        */
        if(is_subclass_of($class, UserInterface::class)) {
            unset($body['password']);
            unset($body['primarySpecification']);
        }
        if(is_subclass_of($class, OrganizationInterface::class)) {
            unset($body['primarySpecification']);
            if($class === Tenant::class) {
                unset($body['organization']);
            }
        }
        if($class === CustomSpecification::class) {
            unset($body['parent']);
        }
        if($class === Project::class) {
            unset($body['defaultSpecification']);
        }
        if($class === Document::class) {
            unset($body['specification']);
            unset($body['resource']);   // Why is this being returned in my faker generator?
        }
        return $body;
    }

    public function echo():self
    {
        print_r($this->toArray());
        return $this;
    }

    public function toArray():array
    {
        return $this->getResponse()->getStatusCode()===204?[]:$this->getResponse()->toArray();
    }

    public function toEntity():mixed
    {
        if(!$this->subjectEntity) {
            $this->subjectEntity =  $this->entityManager->find($this->class, $this->getIds());
        }
        return $this->subjectEntity;
    }

    private function getIds():array
    {
        $arr = $this->getResponse()->toArray();
        if(is_subclass_of($this->class, AclInterface::class)) {
            $ids = ['id' => Ulid::fromString(explode('/', $arr['@id'])[2])];
        }
        elseif(is_subclass_of($this->class, AclMemberInterface::class)) {
            $ps = explode('/', ltrim($arr['@id'], '/'));
            if(count($ps)===5) {
                $ids = array_combine($this->identifier, [Ulid::fromString($ps[1]), Ulid::fromString($ps[3])]);
            }
            else {
                $ids = [];
                foreach(explode(';', end($ps)) as $p) {
                    $p = explode('=', $p);
                    $ids[$p[0]] = Ulid::fromString($p[1]);
                }
            }
        }
        else {
            $ids = [];
            foreach(array_intersect_key($arr, array_flip($this->identifier)) as $n=>$v) {
                if(Ulid::isValid($v)) {
                    $v = Ulid::fromString($v);
                }
                $ids[$n]=$v;
            }
        }
        return $ids;
    }
}
