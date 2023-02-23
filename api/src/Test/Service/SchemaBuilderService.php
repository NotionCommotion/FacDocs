<?php

declare(strict_types=1);

namespace App\Test\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Uid\Ulid;
use Faker\Generator as FakerGenerator;
use App\DataFixtures\Provider\TestHelperProvider;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Project\Project;

use App\Test\Model\Schema\FakerProperty;
use App\Test\Model\Schema\IriCollectionProperty;
use App\Test\Model\Schema\IriReferenceProperty;
use App\Test\Model\Schema\NotCompleteProperty;
use App\Test\Model\Schema\PropertyInterface;
use App\Test\Model\Schema\RequestCollection;
use App\Test\Model\Schema\Request;
use App\Test\Model\Schema\Response;
use App\Service\DebugTesterService;
use ArrayObject;

/*
// Follow was an uncessful attempt to find a way to get the target class of various routes.
use Symfony\Component\Routing\RouterInterface;
use ApiPlatform\Symfony\Routing\Router as ApiRouter;
use Symfony\Bundle\FrameworkBundle\Command\RouterDebugCommand;
use Symfony\Bundle\FrameworkBundle\Routing\Router as FBRouter;
use ApiPlatform\Bridge\Symfony\Routing\RouterOperationPathResolver;
use Symfony\Component\Routing\RequestContext;
use ApiPlatform\Symfony\Routing\ApiLoader;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
*/

final class SchemaBuilderService
{
    public function __construct(
        private array $excludedPropertyLinks,   // What is this?
        private EntityManagerInterface $entityManager,
        private SchemaFactoryInterface $schemaFactory,
        private OpenApiFactoryInterface $openApiFactory,
        private CacheInterface $cacheInterface,

        //private DebugTesterService $debugTesterService,

        private bool $enableCache=true,
    )
    {
    }

    public function getExcludedPropertyLinks():array
    {
        return $this->excludedPropertyLinks;
    }
    private function isExcludedProperty(string $class, string $propertyName)
    {
        return ($arr = $this->excludedPropertyLinks[$class]??null)?in_array($propertyName, $arr):false;
    }

    public function debugRequestCollection(string $filter=null)
    {
        return $this->createRequestCollection(true, true)->debug($filter);
    }

    // Only used to visually look at data in order to figure out stuff.
    public function getOpenApi(string $filter=null)
    {
        $openApiSchemas = ($this->openApiFactory)(['base_url'=>'/'])->getComponents()->getSchemas();
        if(!$filter) {
            return $openApiSchemas;
        }
        $filteredOpenApiSchemas = [];
        foreach($openApiSchemas as $key=>$value) {
            if(stripos($key, $filter)!==false) {
                $filteredOpenApiSchemas[$key] = $value;
            }
        }
        return $filteredOpenApiSchemas;
    }

    // Only used to visually look at data in order to figure out stuff.
    public function getAllSchemas(string $filter=null):array
    {
        $arr = [];
        $openApiSchemas = ($this->openApiFactory)(['base_url'=>'/'])->getComponents()->getSchemas();
        foreach($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $classname = $metadata->getName();
            if($filter && ($parts = explode('\\', $classname)) && stripos($parts[count($parts)-1], $filter)===false) {
                continue;
            }
            $schema = $this->schemaFactory->buildSchema($classname, 'json', Schema::TYPE_INPUT);
            $arr[$classname] = $this->iterate($schema);
        }
        return $arr;
    }

    public function fetchRequestCollection(bool $debug=false, bool $disableCache=false):RequestCollection
    {
        return $this->enableCache && !$disableCache
        ?$this->cacheInterface->get('entity_schema', function() use($debug) {return $this->createRequestCollection($debug);})
        :$this->createRequestCollection($debug);
    }

    private function iterate($input):array
    {
        $arr = [];
        foreach($input as $k=>$i) {
            $arr[$k] = is_iterable($i)?$this->iterate($i):$i;
        }
        return $arr;
    }

    private function getFakerMap(\ReflectionClass $reflection):array
    {
        return [
            'password' => 'password',
            'username' => 'username',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'email' => 'email',
            'timezone' => 'timezone',
            'url' => 'url',
            'html' => 'randomHtml',
            'name' => 'company',
            'topic' => 'sentence',
            'description' => 'sentence',
            'projectTeamDescription' => 'sentence',
            'subject' => 'sentence',
            'content' => 'sentence',
            'keywords' => 'sentence',
            'spec' => 'randomNumber',
            'message' => 'sentence',
            'website' => 'url',
            'projectId' => 'uuid',
            'roles' => lcfirst($reflection->getShortName()).'Roles',
            'request' => 'words',
        ];
    }

    private function createRequestCollection(bool $debug=false):RequestCollection
    {
        if($debug)echo(PHP_EOL);
        $requestCollection = new RequestCollection();
        $openApiSchemas = [];
        $records = ($this->openApiFactory)(['base_url'=>'/'])->getComponents()->getSchemas();
        foreach ($records as $name=>$record) {
            $key=explode('.', explode('-', $name)[0])[0];
            if(!isset($openApiSchemas[$key])) {
                $openApiSchemas[$key] = $record;
                continue;
            }
            if(!$record->offsetExists('properties')) {
                continue;
            }
            if($openApiSchemas[$key]->offsetExists('properties')) {
                $openApiSchemas[$key]->offsetSet('properties', array_merge($record->offsetGet('properties'), $openApiSchemas[$key]->offsetGet('properties')));
            }
            else {
                $openApiSchemas[$key]->offsetSet('properties', $record->offsetGet('properties'));
            }
        }
        $noRef=false;
        //DebugBreak();
        foreach($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $reflection = $metadata->getReflectionClass();
            $classname = $reflection->getName();
            $fakerMap = $this->getFakerMap($reflection);

            $isApiRecord = $this->hasAttribute($reflection, ApiResource::class);
            $isAbstract = $reflection->isAbstract();

            $schema = $this->schemaFactory->buildSchema($classname, 'json', Schema::TYPE_INPUT);

            if(!$schema->offsetExists('$ref')) {
                $noRef = true;
                $schema = $this->schemaFactory->buildSchema($classname, 'json');
                if(!$schema->offsetExists('$ref')) {
                    if($debug) printf('%-50s skipped - no $ref'.PHP_EOL, $classname);
                    continue;
                }
            }

            $definitionName = substr($schema->offsetGet('$ref'), 14);
            if(!$schema->offsetGet('definitions')->offsetExists($definitionName)) {
                if($debug) printf('%-50s skipped.  No definitions'.PHP_EOL, $classname);
                continue;
            }

            $definition = $schema->offsetGet('definitions')->offsetGet($definitionName);

            if(!$definition->offsetExists('properties')) {
                if($debug) printf('%-50s skipped - no properties'.PHP_EOL, $classname);
                continue;
            }

            //$key = $this->strReplaceFirst('-', '.jsonld-', $definitionName);
            $key = $reflection->getShortName();
            if(!isset($openApiSchemas[$key])) {
                if($debug) printf('%-50s skipped - no openApiSchema for key %s: and definitionName: %s'.PHP_EOL, $classname, $key, $definitionName);
                continue;
            }
            $openApiSchema = $openApiSchemas[$key]->offsetGet('properties');

            $request = $requestCollection->createRequest($metadata, $isApiRecord, $isAbstract, $noRef);

            if(!$isApiRecord) {
                if($debug) printf('%-50s skipped - Not an ApiRecord'.PHP_EOL, $classname);
                $noRef=false;
                continue;
            }
            if($isAbstract) {
                if($debug) printf('%-50s skipped - Is abstract'.PHP_EOL, $classname);
                $noRef=false;
                continue;
            }

            if($noRef) {
                if($debug) printf('%-50s skipped - No reference'.PHP_EOL, $classname);
                $noRef=false;
                if(!in_array($classname, [Tenant::class, Project::class])){
                    continue;
                }
                if($debug) printf('%-50s NOT skipped - No reference'.PHP_EOL, $classname);
            }

            foreach($definition->offsetGet('properties') as $propertyName=>$property) {

                if($this->isExcludedProperty($classname, $propertyName)) {
                    continue;
                }
                if($this->offsetGet($property, 'type')==='array') {
                    $property = $property->offsetGet('items');
                    if(isset($property['$ref']) || ($property['format']??null) ==='iri-reference'){
                        $request->addProperty(new IriCollectionProperty($propertyName));
                        continue;
                    }
                    $property = new ArrayObject($property);
                }

                if($anyOf = $this->offsetGet($property, 'anyOf')) {
                    $property = new ArrayObject($anyOf[0]);
                }

                if($this->offsetGet($property, 'readOnly')) {
                    continue;
                }

                if($format = $this->offsetGet($property, 'format')) {
                    switch($format) {
                        case 'date-time':
                            //$request->addProperty(new FakerProperty($propertyName, 'iso8601'));   // Faker incorrectly doesn't include colon for iso8601.
                            $request->addProperty(new FakerProperty($propertyName, 'date', 'c'));
                            break;
                        case 'iri-reference':
                            $request->addProperty(IriReferenceProperty::create($propertyName, $openApiSchema, $request));
                            break;
                        case 'email':
                            $request->addProperty(new FakerProperty($propertyName, 'email'));
                            break;
                        case 'binary':
                            // Ignore
                            break;
                        default:
                            throw new \Exception(sprintf('classname: %s propertyName: %s format: %s json: %s'.PHP_EOL, $classname, $propertyName, $format, json_encode($property)));
                    }
                    continue;
                }

                if($ref = $this->offsetGet($property, '$ref')) {
                    if($this->isClass($ref, 'PhoneNumber')) {
                        $request->addProperty(new FakerProperty($propertyName, 'e164PhoneNumber'));
                    }
                    elseif($this->isClass($ref, 'Money')) {
                        $request->addProperty(new FakerProperty($propertyName, 'money'));
                    }
                    elseif($this->isClass($ref, 'Location')) {
                        $request->addProperty(new FakerProperty($propertyName, 'location'));
                    }
                    elseif($this->isClass($ref, 'AclPermissionSet')) {
                        $request->addProperty(new FakerProperty($propertyName, 'resourceAclPermissionSet'));
                    }
                    elseif($this->isClass($ref, 'AclPermissionSet')) {
                        $request->addProperty(new FakerProperty($propertyName, 'documentAclPermissionSet'));
                    }
                    elseif($this->isClass($ref, 'AclPermission')) {
                        $request->addProperty(new FakerProperty($propertyName, 'resourceAclPermission'));
                    }
                    elseif($this->isClass($ref, 'AclPermission')) {
                        $request->addProperty(new FakerProperty($propertyName, 'documentAclPermission'));
                    }
                    elseif($this->isClass($ref, 'AbstractDocumentAcl')) {
                        $request->addProperty(new FakerProperty($propertyName, 'documentAcl'));
                    }
                    elseif($this->classOneOf($ref, ['ArchiveResourceAcl', 'TemplateResourceAcl', 'AssetResourceAcl', 'DocumentGroupResourceAcl', 'VendorResourceAcl', 'ProjectResourceAcl', 'CustomSpecificationResourceAcl', 'TenantUserResourceAcl', 'VendorUserResourceAcl'])) {
                        $request->addProperty(new FakerProperty($propertyName, 'resourceAcl'));
                    }
                    elseif($this->isClass($ref, 'Status')) {
                        $request->addProperty(new NotCompleteProperty($propertyName));
                    }
                    elseif($this->classOneOf($ref, ['ResourceAclMember', 'DocumentAclMember', 'AbstractOrganization', 'Vendor', 'OverrideSetting', 'Topic'])) {
                        $request->addProperty(IriReferenceProperty::create($propertyName, $openApiSchema, $request, $ref));
                    }
                    else {
                        throw new \Exception(sprintf('$ref %s %s %s'.PHP_EOL, $classname, $propertyName, json_encode($property)));

                    }
                    continue;
                }

                $propertyType = $this->offsetGet($property, 'type');
                if(is_array($propertyType)) {
                    if(count($propertyType)===2 && in_array($propertyType[0], ['string']) && $propertyType[1]==='null') {
                        $propertyType = $propertyType[0];
                    }
                    else {
                        throw new \Exception(sprintf('bad stuff %s %s'.PHP_EOL, $propertyName, json_encode($property)));
                    }
                }
                switch($propertyType){
                    case 'string':
                        if(!isset($fakerMap[$propertyName])) {
                            throw new \Exception(sprintf('unknown string type %s %s %s'.PHP_EOL, $classname, $propertyName, json_encode($property)));
                        }
                        $request->addProperty(new FakerProperty($propertyName, $fakerMap[$propertyName]));
                        break;
                    case 'boolean':
                        $request->addProperty(new FakerProperty($propertyName, 'boolean'));
                        break;
                    case 'number':
                        $request->addProperty(new FakerProperty($propertyName, 'randomFloat'));
                        break;
                    case 'integer':
                        $request->addProperty(new FakerProperty($propertyName, 'randomNumber'));
                        break;
                    default:
                        throw new \Exception(sprintf('unknown property type %s %s %s'.PHP_EOL, $classname, $propertyName, json_encode($property)));
                }
            }
        }

        return $requestCollection;
    }

    private function hasAttribute(\ReflectionClass $reflection, string $attribute):bool
    {
        foreach($reflection->getAttributes() as $attr){
            if(($attr->getName() === $attribute)) {
                return true;
            }
        };
        return false;
    }

    private function classOneOf(string $ref, array $classes):bool
    {
        $ref = explode('-', substr($ref, 14))[0];
        foreach($classes as $class){
            if(stripos($ref, $class) !== false) return true;
        }
        return false;
    }

    private function isClass(string $ref, string $class):bool
    {
        return explode('-', substr($ref, 14))[0] === $class;
    }

    private function offsetGet(ArrayObject $item, string $offset)
    {
        return $item->offsetExists($offset)?$item->offsetGet($offset):null;
    }

    private function strReplaceFirst(string $search, string $replace, string $subject)
    {
        $pos = strpos($search, $subject);
        if ($pos !== false) {
            $subject = substr_replace($search, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    private function pascalToSnake(string $input):string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
    private function snakeToPascal($input)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
    }

}
