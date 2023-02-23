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

namespace App\Entity\Interfaces;

/**
 * Before persisting, validate.  Examples are recursive Asset and CustomSpecification entities.
 */
interface RequiresAdditionalValidationInterface
{
}
