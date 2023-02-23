<?php

namespace App\Entity\PhpUnitTest;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(operations: [new GetCollection(),new Get()])]
class PhpUnitTestRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(nullable: true)]
    private array $access = [];

    #[ORM\Column]
    private array $request = [];

    #[ORM\Column]
    private array $response = [];

    #[ORM\Column(nullable: true)]
    private array $asserts = [];

    #[ORM\ManyToOne(inversedBy: 'phpUnitTestRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PhpUnitTest $phpUnitTest = null;

    public function __construct()
    {
        $this->executedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getAccess(): array
    {
        return $this->access;
    }

    public function setAccess(?array $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function setRequest(array $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setResponse(array $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getAsserts(): array
    {
        return $this->asserts;
    }

    public function setAsserts(?array $asserts): self
    {
        $this->asserts = $asserts;

        return $this;
    }

    public function getPhpUnitTest(): ?PhpUnitTest
    {
        return $this->phpUnitTest;
    }

    public function setPhpUnitTest(?PhpUnitTest $phpUnitTest): self
    {
        $this->phpUnitTest = $phpUnitTest;

        return $this;
    }
}
