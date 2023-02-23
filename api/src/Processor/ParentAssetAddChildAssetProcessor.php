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

use Exception;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Asset\Asset;
use Doctrine\ORM\EntityManagerInterface;

class ParentAssetAddChildAssetProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($parentAsset, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        $repo = $this->entityManager->getRepository(Asset::class);
        $childAsset = $repo->findOneBy(['id' => $uriVariables['childId']]);
        if ($childAsset->isRoot()) {
            throw new Exception('Root asset may not be added as a child.');
        }
        // Will throw exception if is recurive (i.e. $parentAsset is a child of $childAsset).
        $repo->validateParentChild($parentAsset, $childAsset);

        $parentAsset->addChild($childAsset);
        $this->entityManager->persist($parentAsset);
        $this->entityManager->flush();

        return $parentAsset;
    }
}
