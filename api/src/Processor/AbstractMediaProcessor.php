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
use App\Entity\Document\UploadableFileInterface;
use App\Entity\User\UserInterface;
use App\Service\PhysicalMediaService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Service\UserRetreiverService;
use Doctrine\ORM\EntityManagerInterface;

// Extend to handle how the file is retrieved.  Currently, only HTTP requerst is supported.
// Tried to decorate the base processor but got excessive memory error.

abstract class AbstractMediaProcessor implements ProcessorInterface
{
    abstract protected function getUploadFile(UploadableFileInterface $uploadableFile): ?UploadedFile;

    public function __construct(private EntityManagerInterface $entityManager, private UserRetreiverService $userRetreiverService, private PhysicalMediaService $physicalMediaService)
    {
    }

    public function process($media, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        if (($uploadedFile = $this->getUploadFile($media)) === null) {
            throw new BadRequestHttpException('"file" is required');
        }

        $user = $this->getUser();
        $media
        ->setPhysicalMedia($this->physicalMediaService->upload($uploadedFile, $user->getTenant()))
        ->setFilename($uploadedFile->getClientOriginalName())
        ->setMediaType($this->physicalMediaService->getClientMimeType($uploadedFile));
        
        $this->entityManager->persist($media);
        $this->entityManager->flush();
        return $media;
    }

    protected function getUser(): UserInterface
    {
        return $this
        ->userRetreiverService
        ->getUser();
    }
}
