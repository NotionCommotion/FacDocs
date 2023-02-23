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

namespace App\Test\Model\Api;
use ApiPlatform\Symfony\Bundle\Test\Response;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApiRequest
{
    private const DEFAULT_CONTENT_TYPE_HEADER = 'application/json';
    private const DEFAULT_ACCEPT_HEADER = 'application/ld+json';

    private string $contentTypeHeader;
    private string $acceptHeader;
    private string $method;
    public function __construct(string $method, private string $path, private array $body, private array $extra, private array $headers, private string $charset, private ?bool $isCollection=null)
    {
        $this->method = strtoupper($method);
        $headers = array_map('strtolower', $headers);
        $this->contentTypeHeader = $headers['content-type']??self::DEFAULT_CONTENT_TYPE_HEADER;
        $this->acceptHeader = $headers['accept']??self::DEFAULT_ACCEPT_HEADER;
        if(is_null($isCollection)){
            $pathParts=explode('/', $path);
            $this->isCollection = !Ulid::isValid(end($pathParts));
        }
    }

    public static function create(...$args):self
    {
        return new self(...$args);
    }

    public function getMethod():string
    {
        return $this->method;
    }
    public function getPath():string
    {
        return $this->path;
    }
    public function getBody():array
    {
        return $this->body;
    }
    public function getExtra():array
    {
        return $this->extra;
    }
    public function getHeaders():array
    {
        return $this->headers;
    }

    public function getContentTypeHeader():string
    {
        return $this->contentTypeHeader;
    }
    public function getAcceptHeader():string
    {
        return $this->acceptHeader;
    }

    public function getCharset():string
    {
        return $this->charset;
    }
    public function getExpectedContentType():string
    {
        return sprintf('%s; charset=%s', $this->acceptHeader, $this->charset);
    }

    public function isCollection():bool
    {
        return $this->isCollection;
    }

    public function getData():array
    {
        return ['headers' => $this->headers, 'json'=>$this->body, 'extra'=>$this->extra];
    }

    public function getFile():?UploadedFile
    {
        return $this->extra['files']['file']??null;
    }

    public function debug(bool $verbous=false):array
    {
        $arr = ['method' => $this->method, 'path'=>$this->path, 'class'=>$this::class];
        return $verbous?array_merge(['body' => $this->body, 'extra'=>$this->extra, 'headers'=>$this->headers, 'contentTypeHeader'=>$this->contentTypeHeader, 'acceptHeader'=>$this->acceptHeader], $arr): $arr;
    }

    public function getAnticipatedStatusCode():int
    {
        return match ($this->method) {
            'GET'       => 200,
            'POST'      => 201, //$this->isCollection?201:200,
            'PUT'       => 200,
            'PATCH'     => 200,
            'DELETE'    => 204,
            default => throw new \Exception(sprintf('HTTP Method %s is not supportted', $this->method)),
        };
    }

    public function isGet():bool
    {
        return $this->method === 'GET';
    }
    public function isGetItem():bool
    {
        return $this->method === 'GET' && !$this->isCollection();
    }
    public function isGetCollection():bool
    {
        return $this->method === 'GET' && $this->isCollection();
    }
    public function isPost():bool
    {
        return $this->method === 'POST';
    }
    public function isPut():bool
    {
        return $this->method === 'PUT';
    }
    public function isPatch():bool
    {
        return $this->method === 'PATCH';
    }
    public function isUpdate():bool
    {
        return $this->isPut() || $this->isPatch();
    }
    public function isDelete():bool
    {
        return $this->method === 'DELETE';
    }
}
