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

namespace App\Entity\Organization;

use App\Provider\CurrentOrganizationProvider;
use App\Processor\CurrentOrganizationProcessor;
use App\Entity\Location\Location;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\Specification\NaicsCode;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiProperty;
use libphonenumber\PhoneNumber;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Uid\Ulid;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

#[ApiResource(
    uriTemplate: '/organizations/self',
    operations: [
        new Get(),
        new Put()
    ],
    provider: CurrentOrganizationProvider::class,
    //processor: CurrentOrganizationProcessor::class,
    normalizationContext: ['groups' => ['self_org:read', 'identifier:read', 'location:read'],],
    denormalizationContext: ['groups' => ['self_org:write', 'location:write'],],
)]
class CurrentOrganization
{
    #[ApiProperty(identifier: true)]
    #[Groups(['identifier:read'])]
    private ?Ulid $id = null;
    
    #[Groups(['self_org:read', 'self_org:write'])]
    private ?string $name = null;

    #[ApiProperty(openapiContext: ['example' => 'naics_codes/325411'])]
    #[Groups(['self_org:read', 'self_org:write'])]
    private ?NaicsCode $naicsCode=null;

    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'])]
    #[Groups(['self_org:read', 'self_org:write'])]
    private ?AbstractSpecification $primarySpecification=null;

    #[ApiProperty(openapiContext: ['example' => '(415) 555-1212'])]
    #[AssertPhoneNumber()]
    #[Groups(['self_org:read', 'self_org:write'])]
    private ?PhoneNumber $phoneNumber=null;

    #[Groups(['self_org:read', 'self_org:write'])]
    private ?string $website=null;

    //#[ORM\Embedded(class: Location::class)]
    #[Groups(['self_org:read', 'self_org:write'])]
    private $location;

    #[ApiProperty(openapiContext: ['example' => 'America/Los_Angeles'])]
    #[Groups(['self_org:read', 'self_org:write'])]
    private ?string $timezone = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }
    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
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

    public function getNaicsCode(): ?NaicsCode
    {
        return $this->naicsCode;
    }

    public function setNaicsCode(?NaicsCode $naicsCode): self
    {
        $this->naicsCode = $naicsCode;

        return $this;
    }

    public function getPrimarySpecification(): ?AbstractSpecification
    {
        return $this->primarySpecification;
    }

    public function setPrimarySpecification(?AbstractSpecification $primarySpecification): self
    {
        $this->primarySpecification = $primarySpecification;

        return $this;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?PhoneNumber $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }
}
