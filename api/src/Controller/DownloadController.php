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

namespace App\Controller;

use Exception;
use App\Entity\Document\DownloadableFileInterface;
use App\Service\PhysicalMediaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class DownloadController extends AbstractController
{
    public function __construct(private PhysicalMediaService $physicalMediaService)
    {
    }

    public function __invoke(DownloadableFileInterface $data): Response
    {
        if (($physicalMedia = $data->getPhysicalMedia()) === null) {
            throw new Exception('File does not exist');
        }
        $binaryFileResponse = $this->physicalMediaService->getDownloadResponse($physicalMedia);
        $binaryFileResponse->headers->set('Content-Type', $data->getMediaType()->getName());
        $binaryFileResponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $data->getFilename()
        );

        return $binaryFileResponse;
    }
}
