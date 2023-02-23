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
use Doctrine\ORM\EntityManagerInterface;

class CloneProjectProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process($project, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        $time = time();
        $clone = clone $project;
        
        $clone
        ->setTenant($clone->getTenant()) //Just to set publicId
        ->setName($clone->getName().' - '.$time);
        if($projectId = $clone->getProjectId()) {
            $clone->setProjectId($projectId.' - '.$time);
        }
        $this->entityManager->persist($clone);
        $clone->getResourceAcl()->assimilate($project->getResourceAcl());
        $this->entityManager->flush();
        return $clone;
    }
}
