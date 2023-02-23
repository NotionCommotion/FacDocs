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
use App\Service\PhysicalMediaService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Service\UserRetreiverService;
use Doctrine\ORM\EntityManagerInterface;

final class HttpMediaProcessor extends AbstractMediaProcessor
{
    public function __construct(private RequestStack $requestStack, EntityManagerInterface $entityManager, UserRetreiverService $userRetreiverService, PhysicalMediaService $physicalMediaService)
    {
        parent::__construct($entityManager, $userRetreiverService, $physicalMediaService);
    }

    protected function getUploadFile(UploadableFileInterface $uploadableFile): ?UploadedFile
    {
        return $this->requestStack->getCurrentRequest()->files->get('file');
    }
}
