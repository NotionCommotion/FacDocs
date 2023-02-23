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

namespace App\Entity\User;

use App\Provider\CurrentUserProvider;
use App\Processor\CurrentUserProcessor;
use App\Entity\Specification\AbstractSpecification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiProperty;
use libphonenumber\PhoneNumber;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    uriTemplate: '/users/self',
    operations: [
        new Get(),
        new Put()
    ],
    provider: CurrentUserProvider::class,
    processor: CurrentUserProcessor::class,
    normalizationContext: ['groups' => ['self_user:read', 'identifier:read', 'public_id:read'],],
    denormalizationContext: ['groups' => ['self_user:write'],],
)]
class CurrentUser
{
    #[ApiProperty(identifier: true)]
    #[Groups(['identifier:read'])]
    private ?Ulid $id = null;

    #[Groups(['self_user:read', 'self_user:write'])]
    private ?string $email = null;
    #[Groups(['self_user:read', 'self_user:write'])]
    private ?string $username = null;
    #[Groups(['self_user:read', 'self_user:write'])]
    private ?string $firstName = null;
    #[Groups(['self_user:read', 'self_user:write'])]
    private ?string $lastName = null;
    #[Groups(['self_user:read', 'self_user:write'])]
    private PhoneNumber $mobilePhoneNumber;
    #[Groups(['self_user:read', 'self_user:write'])]
    private PhoneNumber $directPhoneNumber;
    #[Groups(['self_user:read', 'self_user:write'])]
    #[ApiProperty(openapiContext: ['example' => 'specifications/00000000000000000000000000'])]
    private ?AbstractSpecification $primarySpecification=null;
    #[Groups(['self_user:read', 'self_user:write'])]
    #[ApiProperty(openapiContext: ['example' => 'departments/00000000000000000000000000'])]
    private Department $department;
    #[Groups(['self_user:read', 'self_user:write'])]
    #[ApiProperty(openapiContext: ['example' => 'job_titles/00000000000000000000000000'])]
    private JobTitle $jobTitle;

    public function getId(): ?Ulid
    {
        return $this->id;
    }
    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }
    public function getMobilePhoneNumber(): PhoneNumber
    {
        return $this->mobilePhoneNumber;
    }

    public function setMobilePhoneNumber(?PhoneNumber $mobilePhoneNumber): self
    {
        $this->mobilePhoneNumber = $mobilePhoneNumber;

        return $this;
    }

    public function getDirectPhoneNumber(): PhoneNumber
    {
        return $this->directPhoneNumber;
    }

    public function setDirectPhoneNumber(?PhoneNumber $directPhoneNumber): self
    {
        $this->directPhoneNumber = $directPhoneNumber;

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

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getJobTitle(): ?JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?JobTitle $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }
}
