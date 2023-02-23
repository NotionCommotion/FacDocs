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

use App\Entity\Archive\Archive;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[AsController]
class DownloadArchiveController extends AbstractController
{
    public function __invoke(Archive $archive): Response
    {
        $filename = $archive->getFilename();
        $binaryFileResponse = new BinaryFileResponse($filename);
        $binaryFileResponse->headers->set('Content-Type', mime_content_type($filename));
        $binaryFileResponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        return $binaryFileResponse;
    }
}
