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

namespace App\DataFixtures\Processor;

use Fidry\AliceDataFixtures\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use App\Entity\User\HashUserPasswordInterface;
use App\Entity\User\UserInterface;

final class UserProcessor implements ProcessorInterface
{
    private $passwordHasher;

    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * @inheritdoc
     */
    public function preProcess($fixtureId, $object): void
    {
        return; // Not used since HashUserPasswordListener used instead
        if (false === $object instanceof HashUserPasswordInterface) {
            return;
        }
        $object->hashPassword($this->passwordHasherFactory);
    }

    /**
     * @inheritdoc
     */
    public function postProcess($fixtureId, $object): void
    {
        // do nothing
    }
}