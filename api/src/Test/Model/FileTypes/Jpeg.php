<?php

declare(strict_types=1);

namespace App\Test\Model\FileTypes;
use Symfony\Component\HttpFoundation\File\File;

final class Jpeg extends AbstractFile implements FileInterface
{
    protected function createFile(int $size, string $pathname)
    {
    }

    public function getRealMimeType():string
    {
        return 'image/jpeg';
    }

    protected function _getValidExtensions():array
    {
        return ['jpg'];
    }
}
