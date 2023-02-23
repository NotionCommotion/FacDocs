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
use App\Entity\Document\Media;
use Doctrine\ORM\EntityManagerInterface;

class DocumentRemoveMediaProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($document, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['id' => $uriVariables['mediaId']]);
        $document->removeMedia($media);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }
}
