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

namespace App\Doctrine\EventSubscriber;

use App\Entity\User\SystemUser;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Service\UserRetreiverService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class BelongsToTenantSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserRetreiverService $userRetreiverService)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof BelongsToTenantInterface) {
            return;
        }

        if (null !== $entity->getTenant()) {
            // Tenant will only be set if a system user using its BelongsToTenantImposterInterface or a newly created tenant making some sample assets, etc.
            return;
        }
        if ($user = $this->userRetreiverService->getUser()) {
            if($tenant = $user->getTenant()) {
                $entity->setTenant($tenant);
            }
            else {
                throw new \Exception(sprintf('User does not have a tenant.  Entity: obj: %s user: %s', json_encode($entity->debug()), json_encode($user->debug())));
            }
        }
        //elseif(!$entity instanceof SystemUser) {
        else {
            throw new \Exception(sprintf('User is missing.  Entity: %s', json_encode($entity->debug())));
        }
    }
}