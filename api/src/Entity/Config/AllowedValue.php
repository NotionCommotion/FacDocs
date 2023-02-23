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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Exposed via service.
 */
#[ORM\Table(indexes: ['(name="valueIndex", columns={"item_value"})'])]
#[ORM\Entity]
class AllowedValue
{
    use IdTrait;

    #[ORM\Column(type: 'string', length: 8)]
    private ?string $itemValue = null;

    #[ORM\Column(type: 'string', length: 6)]
    private ?string $caption = null;

    /**
     * @var Setting[]|Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: OverrideSetting::class, mappedBy: 'allowedValue')]
    private Collection $overrideSettings;

    #[ORM\ManyToOne(targetEntity: Setting::class, inversedBy: 'allowedValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Setting $setting = null;

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'class'=>get_class($this)];
    }

    public function getItemValue(): ?string
    {
        return $this->itemValue;
    }

    public function setItemValue(string $itemValue): self
    {
        $this->itemValue = $itemValue;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(string $caption): self
    {
        $this->caption = $caption;

        return $this;
    }

    public function getSetting(): ?Setting
    {
        return $this->setting;
    }

    public function setSetting(?Setting $setting): self
    {
        $this->setting = $setting;

        return $this;
    }

    // Just to prevent rector from changing?
    public function getOverrideSettings(): ?ArrayCollection
    {
        return $this->overrideSettings;
    }
}
