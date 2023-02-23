<?php

declare(strict_types=1);

namespace App\Test\Model;
use App\Test\Model\FileTypes\FileInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Exception;

final class UploadedMockFile extends UploadedFile
{
    private array $args;
    //public function __construct(string $path, string $originalName, string $mimeType = null, int $error = null, bool $test = false)
    public function __construct(private FileInterface $fileType, ...$args)
    {
        parent::__construct(...$args);
        $this->args = $args;
    }

    public static function create(FileInterface $fileType)
    {
        return new self($fileType, $fileType->getPathname(), $fileType->getFilename(), $fileType->getMimeType());
    }

    public function getFileType(): FileInterface
    {
        return $this->fileType;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function debug(): array
    {
        return get_object_vars($this);
    }

    public function getClientOriginalName(): string
    {
        return $this->fileType->getFilename()??parent::getClientOriginalName();
    }
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->getFilename(), \PATHINFO_EXTENSION);
    }
    public function getClientMimeType2(): string
    {
        return $this->fileType->getClientFakeMimeType();
    }

    public function move(string $directory, string $name = null): File
    {
        throw new Exception('Not implemented');
    }
}
