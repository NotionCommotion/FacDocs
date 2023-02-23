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

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use App\Entity\User\HashUserPasswordInterface;

final class HashUserPasswordListener
{
    public function __construct(private PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function prePersist(HashUserPasswordInterface $user, LifecycleEventArgs $event): void
    {
        $this->handle($user, $event);
    }

    public function preUpdate(HashUserPasswordInterface $user, LifecycleEventArgs $event): void
    {
        $this->handle($user, $event);
    }

    private function handle(HashUserPasswordInterface $user, LifecycleEventArgs $event): void
    {
        $user->hashPassword($this->passwordHasherFactory);
        /*
        $entityManager = $event->getEntityManager();
        $entityManager->persist($data);
        $entityManager->flush();
        */
    }
}
