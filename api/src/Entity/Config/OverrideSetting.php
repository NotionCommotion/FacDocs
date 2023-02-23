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

// use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Organization\TenantInterface;
use Doctrine\ORM\Mapping as ORM;

// use App\Entity\MultiTenenacy\BelongsToTenantInterface;

/**
 * Not an API resource.
 */
#[ORM\Entity]
/*
// Future: Consider having this class use BelongsToTenantTrait and overide to make it a PK.
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: "tenant",
        joinTable: new ORM\JoinTable(name: "tenant"),
        inversedBy: "overrideSettings",
        //joinColumns: [new ORM\JoinColumn(name: "tenant_id", referencedColumnName: "id")],
    ),
])]
#[ORM\AttributeOverrides([
    new ORM\AttributeOverride(
        name: "id",
        column: new ORM\Column(name: "guest_id", type: "integer", length: 140)
    ),
])]
*/
class OverrideSetting // implements BelongsToTenantInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: TenantInterface::class, inversedBy: 'overrideSettings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TenantInterface $tenant = null;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\ManyToOne(targetEntity: 'Setting')]
    private ?Setting $setting = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $unconstrainedValue = null;

    #[ORM\ManyToOne(targetEntity: AllowedValue::class, inversedBy: 'overrideSettings')]
    private ?AllowedValue $allowedValue = null;

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['tenant'=>$this->tenant?$this->tenant->debug($follow, $verbose, $hide):null, 'setting'=>$this->setting?$this->setting->debug($follow, $verbose, $hide):null, 'class'=>get_class($this)];
    }

    public function getTenant(): ?TenantInterface
    {
        return $this->tenant;
    }

    public function setTenant(TenantInterface $tenant): self
    {
        $this->tenant = $tenant;

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

    public function getUnconstrainedValue(): ?string
    {
        return $this->unconstrainedValue;
    }

    public function setUnconstrainedValue(?string $unconstrainedValue): self
    {
        $this->unconstrainedValue = $unconstrainedValue;

        return $this;
    }

    public function getAllowedValue(): ?AllowedValue
    {
        return $this->allowedValue;
    }

    public function setAllowedValue(?AllowedValue $allowedValue): self
    {
        $this->allowedValue = $allowedValue;

        return $this;
    }
}
