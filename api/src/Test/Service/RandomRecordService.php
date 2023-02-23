<?php

declare(strict_types=1);

namespace App\Test\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\TenantInterface;
use App\Entity\ListRanking\RankedListInterface;
use Symfony\Component\Uid\Ulid;

final class RandomRecordService
{
    private array $values=[];
    private array $used=[];
    private array $classInfo=[];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getTenant(bool $unique=false): ?TenantInterface
    {
        $id = $this->entityManager->getConnection()->query('SELECT id FROM tenant OFFSET floor(random() * (SELECT COUNT(*) FROM tenant)) LIMIT 1;')->fetchOne();
        return $id?$this->entityManager->getRepository(Tenant::class)->find($id):null;
    }

    public function getTenantRecordId(Ulid $tenantId, string $class, bool $unique=false, int $counter=0): mixed
    {
        $classInfo = $this->getClassInfo($class);
        $sql = sprintf('SELECT %s FROM public.%s WHERE tenant_id=? OFFSET floor(random() * (SELECT COUNT(*) FROM public.%s WHERE tenant_id=?)) LIMIT 1;', $classInfo->id, $classInfo->tableName, $classInfo->tableName);
        $tenantId = $tenantId->toRfc4122();
        $id = $this->entityManager->getConnection()->prepare($sql)->execute([$tenantId, $tenantId])->fetchOne();
        if($id) {
            if($unique && isset($this->used[$class][$id]) && $counter<20) {
                $id = $this->getTenantRecordId($tenantId, $class, $unique, $counter++);
            }
            $this->used[$class][$id]=true;
        }
        return $id;
    }

    public function getTenantRecord(Ulid $tenantId, string $class, bool $unique=false, int $counter=0): ?object
    {
        return ($id = $this->getTenantRecordId($tenantId, $class, $unique, $counter))?$this->entityManager->getRepository($class)->find($id):null;;
    }

    public function getNonTenantRecordId(string $class, bool $unique=false): mixed
    {
        if(is_subclass_of($class, RankedListInterface::class)) {
            return ($obj = $this->getNonTenantRecord($class, $unique))?$obj->getIdentifier():null;
        }
        else {
            $classInfo = $this->getClassInfo($class);
            $sql = sprintf('SELECT %s FROM public.%s OFFSET floor(random() * (SELECT COUNT(*) FROM public.%s)) LIMIT 1;', $classInfo->id, $classInfo->tableName, $classInfo->tableName);
            if($unique) {
                $counter = 0;
                while(true) {
                    $id = $this->entityManager->getConnection()->query($sql)->fetchOne();
                    if(!isset($this->used[$class][$id])) {
                        $this->used[$class][$id]=true;
                        return $id;
                    }
                    if(count($counter>20)) {
                        return null;
                    }
                    $counter++;
                }
            }
            return $this->entityManager->getConnection()->query($sql)->fetchOne();
        }
    }

    public function getNonTenantRecord(string $class, bool $unique=false): ?object
    {
        if(is_subclass_of($class, RankedListInterface::class)) {
            if(!isset($this->values[$class])) {
                $this->values[$class] = $this->entityManager->getRepository($class)->findAll();
            }
            if($unique) {
                while(true) {
                    $id = rand(0, count($this->values[$class])-1);
                    if(!isset($this->used[$class][$id])) {
                        $this->used[$class][$id]=true;
                        return $this->values[$class][$id];
                    }
                    if(count($this->values[$class]) === count($this->used[$class])) {
                        return null;
                    }
                }
            }
            return $this->values[$class][rand(0, count($this->values[$class])-1)];
        }
        else {
            return ($id = $this->getNonTenantRecordId($class, $unique))?$this->entityManager->getRepository($class)->find($id):null;
        }
    }

    private function getClassInfo(string $class): \stdClass
    {
        if(!isset($this->classInfo[$class])) {
            $meta = $this->entityManager->getClassMetadata($class);
            $this->classInfo[$class] = (object) [
                'tableName'=>$meta->getTableName(),
                'id'=>$meta->getSingleIdentifierFieldName(),
                'compositeId'=>$meta->getIdentifierFieldNames(),
            ];
        }
        return $this->classInfo[$class];
    }
}
