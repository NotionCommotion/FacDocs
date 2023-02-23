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

namespace App\Repository\User;

use App\Entity\User\UserInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\User\BasicUserInterface;

interface UserRepositoryInterface
{
    /* Question - If I make UserRepository abstract, I get error
    "The \"App\\Repository\\User\\UserRepository\" entity repository implements \"Doctrine\\Bundle\\DoctrineBundle\\Repository\\ServiceEntityRepositoryInterface\", but its service could not be found. Make sure the service exists and is tagged with \"doctrine.repository_service\".",

    */
}
