<?php

declare(strict_types=1);

namespace App\Test\Model\FileTypes;
use Symfony\Component\HttpFoundation\File\File;

final class Text extends AbstractFile implements FileInterface
{
    protected function createFile(int $size, string $pathname)
    {        
        $fp = fopen($pathname, 'w');
        /*
        fseek($fp, $size-1,SEEK_CUR);
        fwrite($fp,'a');
        */
        fwrite($fp,str_repeat('a', $size));
        fclose($fp);
        return $fp;
    }

    public function getRealMimeType():string
    {
        return 'text/plain';
    }

    public function getDefaultExtension(): string
    {
        return 'txt';
    }
}