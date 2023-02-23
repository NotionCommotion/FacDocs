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

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\ApiProperty;
use App\Entity\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use libphonenumber\PhoneNumber;
// use Doctrine\ORM\Mapping\MappedSuperclass;
use App\Entity\Location\Location;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\Specification\NaicsCode;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\Trait\UserAction\UserActionTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

// For unknown reasons, if ApiResource is not applied, doesn't exposes createBy as a URI.  Maybe no need but only for AbstractUser
#[NotExposed]
#[ORM\Entity]
#[ORM\Table(name: 'organization')]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap(value: ['tenant' => Tenant::class, 'vendor' => Vendor::class, 'system' => SystemOrganization::class, 'testing' => TestingTenant::class])]
#[ORM\Index(name: 'idx_organization_us_state', columns: ['location_state'])] // See Location embeddable
abstract class AbstractOrganization implements OrganizationInterface, HasUlidInterface
{
    use HasUlidTrait;
    use UserActionTrait;

    // Add the following to prevent errors when installing fixutres.
    //protected ?UserInterface $createBy = null;
    //protected ?UserInterface $updateBy = null;

    #[Groups(['organization:read', 'organization:write'])]
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: UserInterface::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected Collection $users;

    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[Assert\NotBlank(message: 'name must not be blank')]
    #[ORM\Column(type: 'string', length: 255)]  //, unique: true)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: NaicsCode::class)]
    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    #[ApiProperty(readableLink: false, writableLink: false, openapiContext: ['example' => 'naics_codes/325411'])]
    protected ?NaicsCode $naicsCode=null;

    // Comment out next line to prevent errors when installing fixutres.
    #[ORM\ManyToOne(targetEntity: AbstractSpecification::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ApiProperty(readableLink: false, writableLink: false, openapiContext: ['example' => 'specifications/00000000000000000000000000'])]
    protected ?AbstractSpecification $primarySpecification=null;

    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ApiProperty(openapiContext: ['example' => '(415) 555-1212'])]
    #[AssertPhoneNumber()]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    protected ?PhoneNumber $phoneNumber=null;

    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $website=null;

    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ORM\Embedded(class: Location::class)]
    protected $location;

    #[Groups(['organization:read', 'organization:write', 'self_org:read', 'self_org:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[ApiProperty(openapiContext: ['example' => 'America/Los_Angeles'])]
    protected ?string $timezone = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->location = new Location();
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        if($follow>0) {
            $follow--;
        }
        $arr = ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'type'=>$this->getType()->name, 'name'=>$this->name, 'class'=>get_class($this)];
        return $verbose?array_merge($arr, ['createBy'=>$this->createBy?$this->createBy->getFullname():null, 'updateBy'=>$this->updateBy?$this->updateBy->getFullname():null, 'createAt'=>$this->createAt, 'updateAt'=>$this->updateAt]):$arr;
    }

    /**
     * @return Collection<int, UserInterface>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserInterface $user): self
    {
        // Concrete classes override to just confirm adding the correct type of user (i.e. Tenant, Vendor, System).  Maybe not necessary?
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setOrganization($this);
        }

        return $this;
    }

    public function removeUser(UserInterface $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getOrganization() === $this) {
                $user->setOrganization(null);
            }
        }

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
