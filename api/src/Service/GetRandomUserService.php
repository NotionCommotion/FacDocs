<?php
/*
Intention is to be used with fixtures, but currently not used.
*/
declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization\Tenant;
use App\Entity\User\SystemUser;
use App\Entity\User\UserInterface;
use SplObjectStorage;

class GetRandomUserService
{
    private array $systemUsers=[];
    private SplObjectStorage $tenants;
    private ?SystemUser $rootUser=null;
    private int $countRootUser=0;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->tenants = new SplObjectStorage();
    }

    public function getUser(object $entity): UserInterface
    {
        if($entity instanceof SystemUser) {
            $this->systemUsers[] = $entity;
            return $this->getRootUser($args, $entity);
        }
        elseif($entity instanceof Tenant) {
            if(!$this->tenants->contains($entity)) {
                echo('BlameFixtureListener Tenant new'.PHP_EOL);
                $this->tenants->attach($entity);
            }
            return $this->getSystemUser($args);
        }
        else {
            return $this->getTenantUser($args, $entity);
        }
    }

    private function getRootUser(LifecycleEventArgs $args, ?SystemUser $user=null): SystemUser
    {
        if(!$this->rootUser) {
            // RootUser must either be this passed user or in the database.
            if($rootUser = $args->getEntityManager()->getRepository(SystemUser::class)->findRoot()) {
                if($user && $user->isRoot() && $this->countRootUser) {
                    // RootUser is added under AppFixtures outside of Doctrine, so when password is updated, will be persisted for the first time
                    throw new \Exception('Cannot have two root users');
                }
                $this->countRootUser++;
                $this->rootUser = $rootUser;
            }
            elseif(!$user || !$user->isRoot()) {
                throw new \Exception('Missing root users');
            }
            else {
                $this->countRootUser++;
                $this->rootUser = $user;
            }
        }
        return $this->rootUser;
    }

    private function getSystemUser(LifecycleEventArgs $args): SystemUser
    {
        return $this->systemUsers[rand(0, count($this->systemUsers)-1)]??$this->getRootUser($args);
    }

    private function getTenantUser(LifecycleEventArgs $args, $entity): UserInterface
    {
        $users = $entity->getTenant()->getUsers();
        return ($count = $users->count())?$users->get(rand(0, $count-1)):$this->getSystemUser($args);
    }
}
