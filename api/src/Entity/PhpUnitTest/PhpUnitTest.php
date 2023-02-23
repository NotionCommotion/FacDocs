<?php

namespace App\Entity\PhpUnitTest;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(operations: [new GetCollection(),new Get()])]
class PhpUnitTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\OneToMany(mappedBy: 'phpUnitTest', targetEntity: PhpUnitTestRecord::class)]
    private Collection $phpUnitTestRecords;

    public function __construct()
    {
        $this->phpUnitTestRecords = new ArrayCollection();
        $this->startAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * @return Collection<int, PhpUnitTestRecord>
     */
    public function getPhpUnitTestRecords(): Collection
    {
        return $this->phpUnitTestRecords;
    }

    public function addPhpUnitTestRecord(PhpUnitTestRecord $phpUnitTestRecord): self
    {
        if (!$this->phpUnitTestRecords->contains($phpUnitTestRecord)) {
            $this->phpUnitTestRecords->add($phpUnitTestRecord);
            $phpUnitTestRecord->setPhpUnitTest($this);
        }

        return $this;
    }

    public function removePhpUnitTestRecord(PhpUnitTestRecord $phpUnitTestRecord): self
    {
        if ($this->phpUnitTestRecords->removeElement($phpUnitTestRecord)) {
            // set the owning side to null (unless already changed)
            if ($phpUnitTestRecord->getPhpUnitTest() === $this) {
                $phpUnitTestRecord->setPhpUnitTest(null);
            }
        }

        return $this;
    }
}
