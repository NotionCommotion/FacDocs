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

use App\Entity\User\TenantUser;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;

class EntityContainer
{
    private array $entityStack=[];

    public function __construct(private TenantUser $rootUser)
    {
        if(!in_array('ROLE_TENANT_SUPER', $rootUser->getRoles())) {
            throw new \Exception('Admin user must have ROLE_TENANT_SUPER');
        }
    }

    public function addEntity(BelongsToTenantInterface $entity): self
    {
        if($this->rootUser->getTenant()->getId()->toBase32() !== $entity->getTenant()->getId()->toBase32()) {
            throw new \Exception(sprintf('Entity\'s tenant %s must be the same as root users %s.', $entity->getTenant()->getId()->toBase32(), $this->rootUser->getTenant()->getId()->toBase32()));
        }
        $class = get_class($entity);
        $this->entityStack[$class][isset($this->entityStack[$class])?count($this->entityStack[$class]):0] = $entity;
        return $this;
    }

    public function getEntity(string $class, int $position): BelongsToTenantInterface
    {
        if(!isset($this->entityStack[$class][$position])) {
            throw new \Exception("Entity with class $class and position $position does not exist.");
        }
        return $this->entityStack[$class][$position];
    }

    public function getRootUser(): TenantUser
    {
        return $this->rootUser;
    }

    public function getTenant(): Tenant
    {
        return $this->rootUser->getTenant();
    }

    public function debug():array
    {
        return array_map(
            function(string $class, array $entitites){
                return [$class => array_map(function(BelongsToTenantInterface $entity){return $entity->debug();}, $entitites)];
            }, array_keys($this->entityStack), array_values($this->entityStack)
        );
    }
}
