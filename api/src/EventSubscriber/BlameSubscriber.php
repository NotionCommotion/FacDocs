<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use App\Service\UserRetreiverService;
use App\Service\UsesAttributeService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Mapping\Annotation\Blameable;

class BlameSubscriber implements EventSubscriberInterface
{
    private bool $initialized = false;	// Only needs to be set once.

    public function __construct(
        private BlameableListener $blameableListener,
        private UserRetreiverService $userRetreiverService,
        private AuthorizationCheckerInterface $authorizationChecker,
        private UsesAttributeService $usesAttributeService
    )
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preFlush,
        ];
    }

    public function preFlush(EventArgs $args): void
    {
        // Blameable considers a many-to-many addition or removal of a change in the entity, and just listening for preUpdate wasn't enough.
        // See https://forums.phpfreaks.com/topic/315309-transforming-an-object-upon-use;
        $this->setUser($args);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        if($this->initialized) return;
        $this->handle($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        if($this->initialized) return;
        $this->handle($args);
    }

    private function handle(LifecycleEventArgs $args): void
    {
        if (!$this->usesAttributeService->usesAttribute($args->getObject()::class, Blameable::class)) {
            return;
        }
        $this->setUser($args);
    }

    private function setUser(LifecycleEventArgs|EventArgs $args): void
    {
        $this->initialized = true;
        if (($user = $this->userRetreiverService->getTokenUser()) && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // Just passes a reference.  See https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/advanced-configuration.html#reference-proxies
            $this->blameableListener->setUserValue($args->getEntityManager()->getReference($user->getClass(), $user->getId()));
            return;
        }
        // else assume already set.
        //throw new \Exception(sprintf('User not set on %s as required by Blamable', get_class($entity)));
    }
}
