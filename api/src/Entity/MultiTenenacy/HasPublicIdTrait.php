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

namespace App\Entity\MultiTenenacy;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Organization\TenantInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Must add unique constraint between public_id and tenant_id for it to be an api-platform identifier!
 * Either do so in child class or maybe add a listener to do so (https://alexkunin.medium.com/doctrine-symfony-adding-indexes-to-fields-defined-in-traits-a8e480af66b2).
#[ORM\UniqueConstraint(columns: ['public_id', 'tenant_id'])]
 */
trait HasPublicIdTrait
{
    //#[SerializedName('id')]
    //#[ApiProperty(identifier: true)]
    #[Groups(['public_id:read'])]
    #[ORM\Column(type: 'integer')]
    protected ?int $publicId = null;

    public function getPublicId(): ?int
    {
        return $this->publicId;
    }

    // #[Ignore]    //Hack to have a setter but not let ApiPlatform expose it to  allow API-Platform to show as id and not publicId (don't know why).
    public function setPublicId(int $publicId): self
    {
        $this->publicId = $publicId;

        return $this;
    }

    abstract public function getPublicIdIndex(): ?string;

    // Override parent to set entities publicid.
    public function setTenant(TenantInterface $tenant): self
    {
        if ($this->tenant && $this->tenant!==$tenant) {
            throw new \Exception('Tenant cannot be changed');
        }
        $this->tenant = $tenant;

        $tenant->setEntityPublicId($this);

        return $this;
    }
}
