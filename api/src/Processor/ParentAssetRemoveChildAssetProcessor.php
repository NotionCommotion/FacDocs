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
use App\Entity\Asset\Asset;
use Doctrine\ORM\EntityManagerInterface;

class ParentAssetRemoveChildAssetProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($parentAsset, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        // No need to check if root asset since it could never be added in the first place.
        $childAsset = $this->entityManager->getRepository(Asset::class)->findOneBy(['id' => $uriVariables['childId']]);
        $parentAsset->removeChild($childAsset);
        $this->entityManager->persist($parentAsset);
        $this->entityManager->flush();

        return $parentAsset;
    }
}
