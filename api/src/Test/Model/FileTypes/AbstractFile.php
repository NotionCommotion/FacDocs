<?php

declare(strict_types=1);

namespace App\Test\Model\FileTypes;
use SplFileInfo;

abstract class AbstractFile
{
    private SplFileInfo $fileInfo;
    private $fp;

    public function __construct(int $size, private ?string $fakeMimeType=null, private ?string $filename=null, private ?string $path = null)
    {
        $path = $path??sys_get_temp_dir();
        $pathname = sprintf('%s/%s', $path, $filename??(sprintf('mockfile_%s.%s', time(), $this->getDefaultExtension())));
        $this->fp = $this->createFile($size, $pathname);
        $this->fileInfo = new SplFileInfo ($pathname);
    }

    abstract protected function createFile(int $size, string $path);

    public function getMimeType():string
    {
        return $this->getFakeMimeType()??$this->getRealMimeType();
    }

    public function getFakeMimeType(): ?string
    {
        return $this->fakeMimeType;
    }

    public function getDefaultExtension(): string
    {
        return explode('/', $this->getMimeType())[1];
    }

    public function getValidExtensions():array
    {
        return array_merge([$this->getDefaultExtension()], $this->_getValidExtensions());
    }
    protected function _getValidExtensions():array
    {
        return [];
    }

    // Remainder handled by SplFileInfo.
    public function getExtension(): string
    {
        return $this->fileInfo->getExtension();
    }

    public function getFilename(): string
    {
        return $this->fileInfo->getFilename();
    }

    public function getPath(): string
    {
        return $this->fileInfo->getPath();
    }

    public function getPathname(): string
    {
        return $this->fileInfo->getPathname();
    }

    public function getSize(): int
    {
        try {
            return $this->fileInfo->getSize();
        }
        catch(\Exception $e) {
            return filesize($this->getPathname()).'xxxxxxxxxxxxxx';
        }
    }

    public function getType(): string
    {
        return $this->fileInfo->getType();
    }

    public function getDescription(): string
    {
        return sprintf('%s with %s bytes and type %s', $this->getFilename(), $this->getSize(), $this->getMimeType());
    }
}