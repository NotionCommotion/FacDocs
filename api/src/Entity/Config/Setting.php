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

namespace App\Entity\Config;

use App\Entity\Trait\IdTrait;
use App\Repository\Setting\SettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Exposed via service.
 */
#[ORM\Table(indexes: ['(name="nameSettingsInd", columns={"name"})'])]
#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    use IdTrait;

    #[ORM\Column(type: 'string', length: 180)]
    private ?string $name = null;

    /*
    Future to allow vendor's to have settings
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $entity = null;
    */

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $constrained = null;

    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    private ?string $minVal = null;

    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    private ?string $maxVal = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $defaultValue = null;

    #[ORM\ManyToOne(targetEntity: SettingType::class)]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'type', nullable: false)]
    private ?SettingType $type = null;

    #[ORM\ManyToOne(targetEntity: DataType::class)]
    #[ORM\JoinColumn(name: 'data_type', referencedColumnName: 'data_type', nullable: false)]
    private ?DataType $dataType = null;

    /**
     * @var AllowedValue[]|Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: AllowedValue::class, mappedBy: 'setting')]
    private Collection $allowedValues;

    public function __construct()
    {
        $this->allowedValues = new ArrayCollection();
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'class'=>get_class($this)];
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

    public function getConstrained(): ?bool
    {
        return $this->constrained;
    }

    public function setConstrained(bool $constrained): self
    {
        $this->constrained = $constrained;

        return $this;
    }

    public function getMinVal(): ?string
    {
        return $this->minVal;
    }

    public function setMinVal(?string $minVal): self
    {
        $this->minVal = $minVal;

        return $this;
    }

    public function getMaxVal(): ?string
    {
        return $this->maxVal;
    }

    public function setMaxVal(?string $maxVal): self
    {
        $this->maxVal = $maxVal;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getType(): ?SettingType
    {
        return $this->type;
    }

    public function setType(?SettingType $settingType): self
    {
        $this->type = $settingType;

        return $this;
    }

    public function getDataType(): ?DataType
    {
        return $this->dataType;
    }

    public function setDataType(?DataType $dataType): self
    {
        $this->dataType = $dataType;

        return $this;
    }

    /**
     * @return Collection|AllowedValue[]
     */
    public function getAllowedValues(): Collection
    {
        return $this->allowedValues;
    }

    public function addAllowedValue(AllowedValue $allowedValue): self
    {
        if (!$this->allowedValues->contains($allowedValue)) {
            $this->allowedValues[] = $allowedValue;
            $allowedValue->setSetting($this);
        }

        return $this;
    }

    public function removeAllowedValue(AllowedValue $allowedValue): self
    {
        if (!$this->allowedValues->removeElement($allowedValue)) {
            return $this;
        }

        if ($allowedValue->getSetting() !== $this) {
            return $this;
        }

        $allowedValue->setSetting(null);

        return $this;
    }
}
