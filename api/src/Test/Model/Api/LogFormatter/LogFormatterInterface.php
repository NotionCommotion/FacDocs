<?php

declare(strict_types=1);

namespace App\Test\Model\Api\LogFormatter;
use App\Test\Service\TestLoggerService;

interface LogFormatterInterface
{
    public function process(TestLoggerService $testLoggerService):void;
    /*
    public function displayRequest(string $method, string $path, array $data):void;
    public function displayValidResponse(Response $response, string $method, string $path, array $data):void;
    public function displayInalidResponse(Response $response, string $method, string $path, array $data):void;
    public function displayRequestVerbose(?string $message, ?int $anticipatedStatus, string $method, string $path, array $body, array $extra, string $contentType, array $headers, string $class, bool $isCollection):void;
    public function displayValidResponseVerbose(ResponseInterface $entityResponse, ?string $message, ?int $anticipatedStatus, string $method, string $path, array $body, array $extra, string $contentType, array $headers, string $class, bool $isCollection):void;
    public function displayInvalidResponseVerbose(ResponseInterface $entityResponse, ?string $message, ?int $anticipatedStatus, string $method, string $path, array $body, array $extra, string $contentType, array $headers, string $class, bool $isCollection):void;
    */
}
