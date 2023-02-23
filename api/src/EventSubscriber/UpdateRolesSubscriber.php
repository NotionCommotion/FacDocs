<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Acl\HasRolesInterface;
use App\Entity\Acl\Role;

class UpdateRolesSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->handle($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->handle($args);
    }

    private function handle(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        if (!$object instanceof HasRolesInterface) {
            return;
        }
        $roles = array_diff($object->getRoles(), ['ROLE_USER']);
        $roleObjects = $args->getEntityManager()->getRepository(Role::class)->findBy(['id' => $roles]);
        if($err = array_diff($roles, array_map(function(Role $role){return $role->getId();}, $roleObjects))) {
            throw new \Exception('Following roles are not supported: '.implode(', ', $err));
        }
        $object->setRoleConstraint(new ArrayCollection($roleObjects));
    }
}
