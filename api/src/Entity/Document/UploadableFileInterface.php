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

namespace App\Entity\Document;

//use App\Entity\Trait\UserAction\UserUploadActionInterface;
use Symfony\Component\HttpFoundation\File\File;

interface UploadableFileInterface   // extends UserUploadActionInterface
{
    public function setPhysicalMedia(?PhysicalMedia $physicalMedia): self;

    public function setFilename(string $filename): self;

    public function setMediaType(?MediaType $mediaType): self;

    public function setFile(File $file): self;  // Verify if needed.
}
