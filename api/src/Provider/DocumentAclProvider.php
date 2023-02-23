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

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\DebugTesterService;

final class DocumentAclProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private DebugTesterService $DebugTesterService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        throw new \Exception(get_class($this).' not currently being used.');
        $uriVariable = $operation->getUriVariables()['id'];
        $resource = $this->entityManager
        ->getRepository($uriVariable->getFromClass())
        ->findOneBy([$uriVariable->getIdentifiers()[0] => $uriVariables['id']]);
        return $resource?$resource->getDocumentAcl():null;
    }
}
