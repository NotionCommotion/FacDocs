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

namespace App\Test\Model\Api;

// Not currently being used.  Concept is that an array of these are given to EntityResponse::assert() and the affect outcome. 
enum AssertEnum: int
{
    case DoThis;
    case DoThat;

    public function assert(): string
    {
        return match($this) {
            static::DoThis => function(){},
            static::DoThat => function(){},
        };
    }
}
