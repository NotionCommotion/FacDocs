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

namespace App\Service;

use App\Entity\Document\DownloadableFileInterface;
use App\Entity\Document\MediaType;
// use Symfony\Component\HttpFoundation\UrlHelper;
use App\Entity\Document\PhysicalMedia;
use App\Entity\Organization\TenantInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Creates a directory name for the file being uploaded.
 * Will use the object's PK if known, otherwise will need to query the DB to get the next one (how to make better?).
 * Directory will be something like id_1_to_1000, id_1001_to_2000, etc.
 */
final class PhysicalMediaService
{
    public function __construct(private string $baseUploadPath, private string $hashAlg, private EntityManagerInterface $entityManager)
    {
    }

    public function upload(UploadedFile $uploadedFile, TenantInterface $tenant): PhysicalMedia
    {
        $mimeType = $uploadedFile->getMimeType();
        if (($mediaType = $this->entityManager->getRepository(MediaType::class)->findOneBy(['id' => $mimeType])) === null) {
            throw new FileException(sprintf('Media Type %s is not supported', $mimeType));
        }
        /*
        if(!$tenant->supportsMediaType($mediaType)) {
            throw new \Exception(sprintf('Media type %s is not allowed', $mediaTypeString));
        }
        */
        if (!$this->entityManager->getRepository($tenant::class)->supportsMediaType($tenant, $mediaType)) {
            throw new FileException(sprintf('Media Type %s is not supported', $mimeType));
        }
        if (!$mediaType->supportsExtension($uploadedFile->getClientOriginalExtension())) {	// instead of getExtension()?
            throw new FileException(sprintf('Extension %s is not supported by Media Type %s', $uploadedFile->getExtension(), $mimeType));
        }
        $repo = $this->entityManager->getRepository(PhysicalMedia::class);
        $size = $uploadedFile->getSize();
        $hash = hash_file($this->hashAlg, $uploadedFile->getPathname());
        foreach ($repo->findBy(['size' => $size, 'mediaType' => $mediaType, 'hash' => $hash]) as $physicalMedia) {
            if ($this->compareFiles($uploadedFile->getPathname(), $this->getPathname($physicalMedia))) {
                return $physicalMedia;
            }
        }
        $physicalMedia = new PhysicalMedia();
        $physicalMedia->setSize($size)->setHash($hash)->setMediaType($mediaType);
        $this->entityManager->persist($physicalMedia);
        $this->moveFile($uploadedFile, $physicalMedia);
        // $this->entityManager->flush();
        return $physicalMedia;
    }

    public function delete(PhysicalMedia $physicalMedia): bool
    {
        return unlink($this->getPathname($physicalMedia));
    }

    public function getDownloadResponse(PhysicalMedia $physicalMedia): BinaryFileResponse
    {
        return new BinaryFileResponse($this->getPathname($physicalMedia));
    }

    public function getClientMimeType(UploadedFile $uploadedFile): MediaType
    {
        return $this->entityManager->getRepository(MediaType::class)->findOneBy(['id' => $uploadedFile->getClientMimeType()]);
    }

    public function getPath(PhysicalMedia $physicalMedia): string
    {
        return sprintf('%s/%s', $this->baseUploadPath, $physicalMedia->getPath());
    }

    public function getPathname(PhysicalMedia $physicalMedia): string
    {
        return sprintf('%s/%s', $this->baseUploadPath, $physicalMedia->getPathname());
    }

    private function moveFile(UploadedFile $uploadedFile, PhysicalMedia $physicalMedia): void
    {
        $uploadPath = $this->getPath($physicalMedia);
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0770, true);
        }
        $uploadedFile->move($uploadPath, $physicalMedia->getFilename());
    }

    private function compareFiles(string $file1, string $file2): bool
    {
        if (filesize($file1) !== filesize($file2) || filetype($file1) !== filetype($file2)) {
            return false;
        }
        if (!($fp1 = fopen($file1, 'r')) || !($fp2 = fopen($file2, 'r'))) {
            throw new FileException('Cannot oppen file');
        }

        while (!feof($fp1) && ($f1 = fread($fp1, 4096)) !== false) {
            if ($f1 !== fread($fp2, 4096)) {
                fclose($fp1);
                fclose($fp2);

                return false;
            }
        }
        fclose($fp1);
        fclose($fp2);

        return true;
    }
}
