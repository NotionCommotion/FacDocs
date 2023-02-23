<?php

namespace App\DataFixtures\Provider;

use Faker\Provider\Base as BaseProvider;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization\SystemOrganization;
use App\Entity\User\SystemUser;
use App\Entity\Specification\CsiSpecification;
use Symfony\Component\Uid\NilUlid;

final class DefaultValueProvider extends BaseProvider
{
    private array $data=[];
    private SystemOrganization $systemOrganization;
    private array $classInfo=[];

    public function __construct(private EntityManagerInterface $entityManager, private array $classes)
    {
        $this->systemOrganization = $entityManager->getRepository(SystemOrganization::class)->find(new NilUlid);

        foreach($classes as $class) {
            switch(gettype($class)) {
                case 'string':
                    $this->data[lcfirst((new \ReflectionClass($class))->getShortName())] = $entityManager->getRepository($class)->findAll();
                    break;
                case 'array':
                    $this->data[$class['name']] = $entityManager->getRepository($class['class'])->findAll();
                    break;
                default:
                    throw new \Exception('Invalid type: '.gettype($class));
            }
        }
    }

    public function systemOrganization():SystemOrganization
    {
        return $this->systemOrganization;
    }

    public function rootUser():SystemUser
    {
        return $this->systemOrganization->getRootUser();
    }

    public function jobTitle()
    {
        return self::randomElement($this->data['jobTitle']);
    }
    public function department()
    {
        return self::randomElement($this->data['department']);
    }
    public function projectStage()
    {
        return self::randomElement($this->data['projectStage']);
    }
    public function documentStage()
    {
        return self::randomElement($this->data['documentStage']);
    }
    public function documentType()
    {
        return self::randomElement($this->data['documentType']);
    }
    public function helpDeskStatus()
    {
        return self::randomElement($this->data['helpDeskStatus']);
    }

    public function csiSpecification():?object
    {
        return $this->getNonTenantRecord(CsiSpecification::class);
    }

    private function getNonTenantRecord(string $class):?object
    {
        $classInfo = $this->getClassInfo($class);
        $sql = sprintf('SELECT %s FROM public.%s OFFSET floor(random() * (SELECT COUNT(*) FROM public.%s)) LIMIT 1;', $classInfo->id, $classInfo->tableName, $classInfo->tableName);
        $id = $this->entityManager->getConnection()->query($sql)->fetchOne();
        return $id?$this->entityManager->getRepository($class)->find($id):null;
    }
    private function getTenantRecord(Ulid $tenantId, string $class, bool $unique=false, int $counter=0): ?object
    {
        $classInfo = $this->getClassInfo($class);
        $sql = sprintf('SELECT %s FROM public.%s WHERE tenant_id=? OFFSET floor(random() * (SELECT COUNT(*) FROM public.%s WHERE tenant_id=?)) LIMIT 1;', $classInfo->id, $classInfo->tableName, $classInfo->tableName);
        $tenantId = $tenantId->toRfc4122();
        $id = $this->entityManager->getConnection()->prepare($sql)->execute([$tenantId, $tenantId])->fetchOne();
        return $id?$this->entityManager->getRepository($class)->find($id):null;
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