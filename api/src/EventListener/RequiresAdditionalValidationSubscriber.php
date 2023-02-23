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

namespace App\EventListener;

use App\Entity\Interfaces\RequiresAdditionalValidationInterface;
use App\Exception\EntityValidationException;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

// All repositories of entities which implement RequiresAdditionalValidationInterface must have a validate() method which will through an exception if error.
// Used to check for recursive entities in Asset and CustomSpecification, etc.
// How should this be enfoced with an interface?

// Should I also validate the root asset?

class RequiresAdditionalValidationSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->validate($lifecycleEventArgs);
    }

    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->validate($lifecycleEventArgs);
    }

    private function validate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $object = $lifecycleEventArgs->getObject();
        if ($object instanceof RequiresAdditionalValidationInterface) {
            try {
                $lifecycleEventArgs->getEntityManager()->getRepository($object::class)->validate($object);
            } catch (EntityValidationException $e) {
                throw $e;
            }
        }
    }
}
