<?php

declare(strict_types=1);

namespace App\Test\Model\FileTypes;

interface FileInterface
{
    public function getDefaultExtension(): string;
    public function getMimeType(): string;
    public function getRealMimeType(): string;
    public function getFakeMimeType(): ?string;
    public function getValidExtensions():array;
    //public function toStream();

    // Remainder handled by SplFileInfo.
    public function getExtension(): string;
    public function getFilename(): string;
    public function getPath(): string;
    public function getPathname(): string ;
    public function getSize(): int;
    public function getType(): string;
}
