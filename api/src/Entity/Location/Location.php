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

namespace App\Entity\Location;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Embeddable]
// FK from tables that have this embeddable to t_us_state is applied outside of Doctrine in AppFixtures.  Be sure to add index to all entities that use.  See Asset, Project, and DocumentGroup.
class Location
{
    #[Groups(['location:read', 'location:write'])]
    #[ApiProperty(openapiContext: ['example' => '101 Main Street'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[Groups(['location:read', 'location:write'])]
    #[ApiProperty(openapiContext: ['example' => 'Seaside'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $city = null;

    #[Groups(['location:read', 'location:write'])]
    #[ApiProperty(openapiContext: ['example' => 'CA'])]
    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private ?string $state = null;

    #[Groups(['location:read', 'location:write'])]
    #[ApiProperty(openapiContext: ['example' => '91234'])]
    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $zipcode = null;

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state?strtoupper($state):null;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): self
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getFullAddress(): ?string
    {
        return $this->isNull()?null:sprintf('%s %s, %s %s', $this->address, $this->city, $this->state, $this->zipcode);
    }

    public function isNull(): bool
    {
        return is_null($this->address) && is_null($this->city) && is_null($this->state) && is_null($this->zipcode);
    }
}
