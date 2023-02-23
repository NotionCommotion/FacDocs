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

class CurrentUserProcessor implements ProcessorInterface
{
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        /*
        echo(get_class($data).PHP_EOL);
        var_dump($data->getFirstName());
        var_dump($data->getLastName());
        exit;
        */
        return $data;
    }
}
