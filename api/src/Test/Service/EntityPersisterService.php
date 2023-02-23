<?php

/*
* This file is part of the FacDocs project.
*
* (c) Michael Reed villascape@gmail.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace App\Test\Service;

use Gedmo\Blameable\BlameableListener;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User\UserInterface;
use App\Entity\Organization\Tenant;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
//use Symfony\Component\Uid\Ulid;
//use App\Entity\MultiTenenacy\HasUlidInterface;

class EntityPersisterService
{
    private ?UserInterface $rootUser = null;
    public function __construct(private EntityManagerInterface $entityManager, private BlameableListener $blameableListener, private TestHelperService $testHelperService)
    {
    }

    public function saveEntity(BelongsToTenantInterface $entity, ?Tenant $tenant=null, ?UserInterface $user=null, bool $flush=true): self
    {
        if($tenant) {
            $entity->setTenant($tenant);
        }
        elseif(!$entity->getTenant()) {
            throw new \Exception('Tenant must be provided');
        }
        $this->entityManager->persist($entity);
        if($flush) {
            $this->flush($user);
        }
        return $this;
    }
    public function saveTenant(?Tenant $tenant=null, ?UserInterface $user=null, bool $flush=true): self
    {
        $this->entityManager->persist($tenant);
        if($flush) {
            $this->flush($user);
        }
        return $this;
    }

    public function flush(?UserInterface $user=null, ?Tenant $tenant=null): self
    {
        $user = $user??$this->getRootUser($tenant);
        $this->blameableListener->setUserValue($user);
        $this->entityManager->flush();
        return $this;
    }
    public function getRootUser(?Tenant $tenant=null): UserInterface
    {
        if(!$this->rootUser) {
            $this->rootUser = $this->testHelperService->getTestingSystemUser('ROLE_SYSTEM_ADMIN');
        }
        if($tenant) {
            $this->rootUser->impersonate($tenant);
        }
        return $this->rootUser;
    }
}