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

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class ConfigProcessor implements ProcessorInterface
{
    public function process($config, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        return $config;
    }
}
