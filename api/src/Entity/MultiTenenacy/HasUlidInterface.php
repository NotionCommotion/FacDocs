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

namespace App\Entity\MultiTenenacy;
use Symfony\Component\Uid\Ulid;

interface HasUlidInterface extends  \Stringable
{
    public function getId(): ?Ulid;
    public function toRfc4122(): string;
}
