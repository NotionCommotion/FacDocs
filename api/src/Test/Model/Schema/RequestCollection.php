<?php

declare(strict_types=1);

namespace App\Test\Model\Schema;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\Mapping\ClassMetadata;
use App\Test\Service\SchemaFixtureService;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Document\Media;

final class RequestCollection
{
    private array $requestClassMap = [];
    private array $requestPathMap = [];

    public function __construct()
    {
        $this->requestPathMap['/medias'] = new Request(Media::class, 'medias', ['id'], true, $this, true, false, true);
    }

    public function createRequest(ClassMetadata $metadata, bool $isApiRecord, bool $isAbstract, bool $noRef):Request
    {
        $reflection = $metadata->getReflectionClass();
        $request = new Request($reflection->getName(), $metadata->getTablename(), $metadata->getIdentifier(), $reflection->hasProperty('tenant'), $this, $isApiRecord, $isAbstract, $noRef);
        $this->requestClassMap[$request->getClass()] = $request;
        $this->requestPathMap[$request->getPath()] = $request;
        return $request;
    }

    public function getRequests(): array
    {
        return array_values($this->requestClassMap);
    }

    public function getRequest(string $class): Request
    {
        if(!isset($this->requestClassMap[$class])) {
            print_r($this->debug());
            throw new UnknownPropertyException(sprintf('Request for class %s does not exist.', $class));
        }
        return $this->requestClassMap[$class];
    }

    public function getRequestByPath(string $path): Request
    {
        $path = '/'.ltrim($path, '/');
        if(!isset($this->requestPathMap[$path])) {
            // Check if it is an acl or acl member (KLUDGE!)
            list($ids, $parts) = $this->getIdsAndParts($path, true);
            if(count($ids)===1 && in_array($parts[1]??null, ['resource_acl', 'document_acl'])) {
                /*
                This is an ACL
                $path: /assets/01GNSBY0BHNG6BNTNEDJ7AZN4S/resource_acl
                $ids: ['01GNSBY0BHNG6BNTNEDJ7AZN4S']
                $parts: ['asset', 'resource_acl']
                $path =  '/asset_resource_acls'
                */
                $newPath = sprintf('/%s_%ss', $parts[0], $parts[1]);
            }
            elseif(count($ids)===2 && in_array($parts[2]??null, ['resourceMember', 'documentMember'])) {
                /*
                This is an ACL Member
                $path: /assets/01GNSBY0BHNG6BNTNEDJ7AZN4S/users/01GNSBXQD3VV4DP3CH3CKHYNVT/resourceMember
                $ids: ['01GNSBY0BHNG6BNTNEDJ7AZN4S', '01GNSBXQD3VV4DP3CH3CKHYNVT']
                $parts: ['asset', 'user', 'resourceMember']
                $path =  '/resource_acl_members'
                */
                $newPath = sprintf('/%s_acl_members', str_replace('Member', '', $parts[2]));
            }
            else {
                list($ids, $parts) = $this->getIdsAndParts($path, false);
                if(count($ids)===1 && count($parts)===1 && isset($this->requestPathMap['/'.$parts[0]])) {
                    // Something like documents/01GQ031F169TP7JBJRFTEDX4HM
                    $newPath = '/'.$parts[0];
                }
            }
            if($newPath??null) {
                //printf('Path: "%s" changed to "%s"'.PHP_EOL, $path, $newPath);
                return $this->getRequestByPath($newPath);
            }
            throw new UnknownPropertyException(sprintf('Request for path %s does not exist. ids: %s. parts: %s.%sdebug: %s', $path, implode(', ', $ids), implode(', ', $parts), PHP_EOL, json_encode($this->debug())));
        }
        return $this->requestPathMap[$path];
    }
    private function getIdsAndParts(string $path, bool $makeSingular=false): array
    {
        $parts=[];
        $ids=[];
        foreach(explode('/', ltrim($path, '/')) as $i=>$part) {
            if(Ulid::isValid($part)) {
                $ids[] = $part;
                $parts[$i-1] = $makeSingular?rtrim($parts[$i-1], 's'):$parts[$i-1];
            }
            else {
                $parts[$i] = $part;
            }
        }
        $parts = array_values($parts);
        return [$ids, $parts];
    }

    public function getResponse(Ulid $id, SchemaFixtureService $schemaFixtureService):array
    {
        $arr = [];
        foreach($this->requestClassMap as $class=>$request) {
            $arr[$class] = $request->getResponse($id, $schemaFixtureService, $this);
        }
        return $arr;
    }

    // Just for help determining which ones I want to allow them to be overriden when attempting to update.
    public function getAllEntitiesWithProperties():array
    {
        $arr = [];
        foreach($this->requestClassMap as $class=>$request) {
            foreach($request->getProperties() as $property) {
                $arr[$class][] = $property->getName();
            }
        }
        return $arr;
    }

    public function debug(string $filter=null):array
    {
        $rs = [];
        foreach($this->requestClassMap as $class=>$request) {
            if($filter && ($parts = explode('\\', $class)) && stripos($parts[count($parts)-1], $filter)===false) {
                continue;
            }
            $rs['requestClassMap'][$class] = array_merge($request->debug(true), ['class'=>$class]);
        }

        foreach($this->requestPathMap as $path=>$request) {
            $class = $request->getClass();
            if($filter && ($parts = explode('\\', $class)) && stripos($parts[count($parts)-1], $filter)===false) {
                continue;
            }
            $rs['requestPathMap'][$path] = array_merge($request->debug(true), ['path'=>$path]);
        }
        return $rs;
    }
}
