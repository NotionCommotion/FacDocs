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
// use ApiPlatform\Processor\ResumableProcessorInterface;
use App\ArchiveBuilder\ArchiveBuilderService;
use App\Entity\Archive\Archive;
use App\Service\UserRetreiverService;
use Doctrine\ORM\EntityManagerInterface;

class ArchiveCreaterDecoratedProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private ArchiveBuilderService $archiveBuilderService, private UserRetreiverService $userRetreiverService)
    {
    }

    public function process($archive, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        /*
        Question.
        How do I set the tenant using AddTenantToTenantEntityDecoratedProcessor
        but not flush the database until archiveCreatorService sets the filename?
        */
        $archive->setTenant($this->userRetreiverService->getUser()->getTenant());
        $this->archiveBuilderService->create($archive);
        // Causes error: See https://github.com/api-platform/api-platform/issues/2209
        //return $this->decorated->process($archive, $operation, $uriVariables, $context);
        $this->entityManager->persist($archive);
        $this->entityManager->flush();
        return $archive;
    }
}
