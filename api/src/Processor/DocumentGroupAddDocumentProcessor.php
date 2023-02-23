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
use App\Entity\Document\Document;
use Doctrine\ORM\EntityManagerInterface;

class DocumentGroupAddDocumentProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($documentGroup, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        $document = $this->entityManager->getRepository(Document::class)->findOneBy(['id' => $uriVariables['documentId']]);
        $documentGroup->addDocument($document);
        $this->entityManager->persist($documentGroup);
        $this->entityManager->flush();

        return $documentGroup;
    }
}
