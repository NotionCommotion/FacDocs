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

namespace App\Exception;

use App\Entity\Asset\Asset;
use Exception;

final class RecursiveAssetException extends Exception
{
    public function __construct(Asset $parentAsset, Asset ...$recursiveAssets)
    {
        $message = sprintf('%s recursive assets %s.',
            $this->format($parentAsset),
            implode(', ', array_map(function(Asset $a){return $this->format($a);}, $recursiveAssets))
        );
        parent::__construct($message);
    }

    private function format(Asset $a):string
    {
        return sprintf('%s (%s)', $a->getName(), $a->getId());
    }
}
