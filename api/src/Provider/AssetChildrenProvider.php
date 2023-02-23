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
use ApiPlatform\Metadata\CollectionOperationInterface;
use App\Entity\Asset\Asset;
use Doctrine\ORM\EntityManagerInterface;

final class AssetChildrenProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        return $this->entityManager->getRepository(Asset::class)->getChildren($uriVariables['id']);
    }
}
