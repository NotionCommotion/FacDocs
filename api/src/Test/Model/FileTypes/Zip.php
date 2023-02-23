<?php

declare(strict_types=1);

namespace App\Test\Model\FileTypes;
use Symfony\Component\HttpFoundation\File\File;

final class Zip extends AbstractFile implements FileInterface
{
    protected function createFile(int $size, string $pathname)
    {
        $zip = new \ZipArchive();
        if ($zip->open($pathname, ZipArchive::CREATE)!==TRUE) {
            throw new \Exception('cannot open '.$pathname);
        }
        $zip->addFromString("testfile.txt", str_repeat('a', $size));
        $zip->close();
        return $zip;
    }

    public function getRealMimeType():string
    {
        return 'application/zip';
    }
}
