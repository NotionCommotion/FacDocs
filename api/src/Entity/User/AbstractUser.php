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

use App\Entity\Organization\AbstractOrganization;
use Exception;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\NotExposed;
use libphonenumber\PhoneNumber;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use App\Entity\Acl\DocumentAclMember;
use App\Entity\Acl\ResourceAclMember;
use App\Entity\Acl\Role;
use App\Entity\Project\ProjectTeamMember;
use App\Entity\Document\Document;
use App\Entity\Error\Error;
use App\Entity\HelpDesk\Topic;
use App\Entity\Trait\SoftDeleteTrait;
use App\Entity\ListRanking\UserListRanking;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Organization\OrganizationType;
use App\Entity\Organization\TenantInterface;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\Trait\UserAction\UserActionTrait;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Ulid;
use Gedmo\Mapping\Annotation as Gedmo;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use App\Security\TokenUser;

// For unknown reasons, if ApiResource is not applied, doesn't exposes createBy as a URI.  Maybe no need but only for AbstractUser
#[NotExposed]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap(value: ['tenant' => TenantUser::class, 'system' => SystemUser::class, 'vendor' => VendorUser::class])]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(columns: ['email', 'organization_id'])]
#[ORM\UniqueConstraint(columns: ['username', 'organization_id'])]
#[Gedmo\SoftDeleteable(fieldName: 'deleteAt', timeAware: false, hardDelete: true)]
abstract class AbstractUser implements UserInterface, HasUlidInterface
{
    use HasUlidTrait;
    use UserActionTrait;
    use SoftDeleteTrait;
    /**
     * @var string[]
     *               Overriden in child.  Still neet to vet out user roles.
     *               How can I authenticate $roles based on child class?
     *               See https://stackoverflow.com/questions/70023075/how-to-use-symfony-to-validate-parent-entity-based-inherited-child-entity
     *               Maybe use abstract protected function getAvailableRoles()?
     */
    public const ROLES = [
        'ROLE_TENANT_USER', 'ROLE_TENANT_ADMIN', 'ROLE_TENANT_SUPER',
        'ROLE_VENDOR_USER', 'ROLE_VENDOR_ADMIN', 'ROLE_VENDOR_SUPER',
        'ROLE_SYSTEM_USER', 'ROLE_SYSTEM_ADMIN', 'ROLE_SYSTEM_SUPER'
    ];

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ApiProperty(openapiContext: ['example' => 'organizations/00000000000000000000000000'], readableLink: false, writableLink: false)]
    #[Groups(['user:read', 'new_user:write'])]
    protected ?AbstractOrganization $organization=null;

    // Future.  Change to php 8.1 enums?
    // Current approach does not allow duplicated emails or usernames even if deleted.  Probably okay.
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['user:read', 'acl_admin:write'])]
    protected bool $isActive = true;

    #[Groups(['user:read', 'self_user:read', 'acl_admin:write'])]
    // Roles enforced by DB.
    //#[Assert\Choice(choices: self::ROLES, multiple: true, multipleMessage: 'Choose a valid role.')]
    #[ORM\Column(type: 'json')]
    protected array $roles = [];

    // Just used for FK constraints.  Called by event subscriber and has only a single setRoleConstraint() method.
    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'user_role_constraint')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $roleConstraints;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    protected ?string $password = null;

    #[SerializedName('password')]
    #[Groups(['user:write', 'self_user:write'])]
    #[Assert\NotBlank(groups: ['create'], message: 'password must not be blank')]
    protected ?string $plainPassword = null;

    #[Assert\NotBlank(message: 'email must not be blank')]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[ORM\Column(type: 'string', length: 180)]
    protected ?string $email = null;

    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[Assert\NotBlank(message: 'username must not be blank')]
    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $username = null;

    // If user was deleted, copy their username and email to these fields and set original to username_id, etc...
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $originalUsername = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $originalEmail = null;

    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[Assert\NotBlank(message: 'firstname must not be blank')]
    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $firstName = null;

    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[Assert\NotBlank(message: 'lastname must not be blank')]
    #[ORM\Column(type: 'string', length: 255)]
    protected ?string $lastName = null;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProjectTeamMember::class)]
    protected Collection $projectTeamMembers;

    /**
     * @var UserListRanking[]|Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: UserListRanking::class, mappedBy: 'user')]
    #[Ignore]
    protected Collection $userListRankings;

    /**
     * @var Collection|HelpDeskTopic[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'createBy', targetEntity: Topic::class)]
    #[Ignore]
    protected Collection $helpDeskTopics;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Error::class, orphanRemoval: true)]
    protected Collection $errors;

    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[AssertPhoneNumber()]
    #[ApiProperty(openapiContext: ['example' => '(415) 555-1212'])]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    protected ?PhoneNumber $mobilePhoneNumber=null;

    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[AssertPhoneNumber()]
    #[ApiProperty(openapiContext: ['example' => '(415) 555-1212'])]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    protected ?PhoneNumber $directPhoneNumber=null;

    // Not used by SystemUser
    #[ORM\ManyToOne(targetEntity: AbstractSpecification::class)]
    #[Groups(['user:read', 'self_user:read', 'user:write', 'self_user:write'])]
    #[ApiProperty(readableLink: false, writableLink: false, openapiContext: ['example' => 'specifications/00000000000000000000000000'])]
    protected ?AbstractSpecification $primarySpecification=null;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ApiProperty(readableLink: false, writableLink: false, openapiContext: ['example' => '/departments/00000000000000000000000000'])]
    #[Groups(['user:read', 'user:write'])]
    protected ?Department $department=null;

    #[ORM\ManyToOne(targetEntity: JobTitle::class)]
    #[ApiProperty(readableLink: false, writableLink: false, openapiContext: ['example' => 'job_titles/00000000000000000000000000'])]
    #[Groups(['user:read', 'user:write'])]
    protected ?JobTitle $jobTitle=null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DocumentAclMember::class, orphanRemoval: true)]
    #[Groups(['acl_admin:read', 'acl_admin:write'])]
    protected Collection $documentAclMembers;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ResourceAclMember::class, orphanRemoval: true)]
    #[Groups(['acl_admin:read', 'acl_admin:write'])]
    protected Collection $resourceAclMembers;

    // protected ArrayCollection $specifications;
    public function __construct()
    {
        // $this->specifications = new ArrayCollection();
        // $this->documents = new ArrayCollection();
        $this->userListRankings = new ArrayCollection();
        $this->resourceAclMembers = new ArrayCollection();
        $this->documentAclMembers = new ArrayCollection();
        $this->projectTeamMembers = new ArrayCollection();
        $this->helpDeskTopics = new ArrayCollection();
        $this->errors = new ArrayCollection();
        $this->roleConstraints = new ArrayCollection();
    }

    public function toTokenUser():TokenUser
    {
        // Not currently used.
        return new TokenUser($this->getId(), $this->getType(), $this->getRoles(), ($tenant = $this->getTenant())?$tenant->getId():null);
    }

    public function getOrganizationId(): Ulid
    {
        return $this->organization->getId();
    }
    public function getTenantId(): ?Ulid
    {
        return ($tenant = $this->getTenant())?$tenant->getId():null;
    }
    public function getClass(): string
    {
        return get_class($this);
    }

    public function isSame(BasicUserInterface $user): bool
    {
        return $this->getId()->equals($user->getId());
    }

    public function isCoworker(BasicUserInterface $user): bool
    {
        return $this->getOrganizationId()->equals($user->getOrganizationId()) && !$this->isSame($user);
    }

    public function getType(): OrganizationType
    {
        return $this->organization->getType();
    }

    public function isSystemUser(): bool
    {
        return $this->organization->getType()->isSystem();
    }

    public function isTenantUser(): bool
    {
        return $this->organization->getType()->isTenant();
    }

    public function isVendorUser(): bool
    {
        return $this->organization->getType()->isVendor();
    }
    
    public function isSuperUser(): bool
    {
        return (bool) array_intersect($this->roles, ['ROLE_SYSTEM_SUPER', 'ROLE_TENANT_ADMIN', 'ROLE_VENDOR_SUPER']);
    }
    public function isAdminUser(): bool
    {
        return (bool) array_intersect($this->roles, ['ROLE_SYSTEM_ADMIN', 'ROLE_TENANT_ADMIN', 'ROLE_VENDOR_ADMIN']) || $this->isSuperUser();
    }
    public function isNormalUser(): bool
    {
        return !$this->isAdminUser();
    }

    public function getOrganization(): OrganizationInterface
    {
        return $this->organization;
    }

    public function setOrganization(OrganizationInterface $organization): self
    {
        // Concrete classes override to just confirm adding the correct type of organization (i.e. Tenant, Vendor, System).  Maybe not necessary?
        if($this->organization && $this->organization!==$organization) {
            throw new \Exception('Organization may not be changed');
        }
        $this->organization = $organization;
        if(!$this->primarySpecification) {
            $this->primarySpecification = $organization->getPrimarySpecification();
        }
        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        if($follow>0) {
            $follow--;
        }
        // 'createBy'=>$this->createBy?$this->createBy->debug($follow, $verbose, $hide):null, 'updateBy'=>$this->updateBy?$this->updateBy->debug($follow, $verbose, $hide):null, 'createAt'=>$this->createAt, 'updateAt'=>$this->updateAt, 
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'email'=>$this->email, 'fullname'=>$this->getFullname(), 'roles'=>$this->getRoles(), 'type'=>$this->organization?$this->getType():null, 'organization'=>$this->organization?$this->organization->debug($follow, $verbose, $hide):'NULL', 'tenant'=>$this->getTenant()?$this->getTenant()->debug($follow>0?--$follow:$follow, $verbose, $hide):'NULL', 'roles2'=>implode('|',$this->roles), 'class'=>get_class($this)];
    }

    public function hashPassword(PasswordHasherFactoryInterface $passwordHasherFactory): void
    {
        if (null !== $this->plainPassword) {
            $passwordHasher = $passwordHasherFactory->getPasswordHasher($this);
            $this->password = $passwordHasher->hash($this->plainPassword, $this->getSalt());
            $this->eraseCredentials();
        }
    }

    public function getLogon(?string $password=null, $asString=false): array|string
    {
        $logon = ['id'=>$this->getOrganization()->getId()->toRfc4122(), 'email'=>$this->email, 'password'=>$password??$this->plainPassword??'Unknown'];
        return $asString?json_encode($logon):$logon;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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

    // Used if user was deleted.
    public function getOriginalEmail(): ?string
    {
        return $this->originalEmail;
    }

    public function setOriginalEmail(?string $originalEmail): self
    {
        $this->originalEmail = $originalEmail;

        return $this;
    }

    public function getOriginalUsername(): ?string
    {
        return $this->originalUsername;
    }

    public function setOriginalUsername(?string $originalUsername): self
    {
        $this->originalUsername = $originalUsername;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    /**
     * @see UserInterface
     *
     * @return mixed[]
     */
    public function getRoles(): array
    {
        return $this->roles?$this->roles:['ROLE_USER'];
    }

    /**
     * @param mixed[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
    public function addRole(string $role): self
    {
        if(!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }

        return $this;
    }
    public function removeRole(string $role): self
    {
        $this->roles = array_diff($this->roles, [$role]);

        return $this;
    }
    #[Ignore]
    public function setRoleConstraint(Collection $roles): self
    {
        $this->roleConstraints = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        //throw new \Exception('Remove setPassword()?  Or maybe remove setPlainPassword() and use setPassword to set the plain password?');
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): void
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
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

    public function getFullname(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @return Collection<int, DocumentAclMember>
     */
    public function getDocumentAclMembers(): Collection
    {
        return $this->documentAclMembers;
    }

    public function addDocumentAclMember(DocumentAclMember $documentAclMember): self
    {
        if (!$this->documentAclMembers->contains($documentAclMember)) {
            $this->documentAclMembers->add($documentAclMember);
            $documentAclMember->setUserx($this);
        }

        return $this;
    }

    public function removeDocumentAclMember(DocumentAclMember $documentAclMember): self
    {
        if ($this->documentAclMembers->removeElement($documentAclMember)) {
            // set the owning side to null (unless already changed)
            if ($documentAclMember->getUserx() === $this) {
                $documentAclMember->setUserx(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ResourceAclMember[]
     */
    public function getResourceAclMembers(): Collection
    {
        return $this->resourceAclMembers;
    }

    public function addResourceAclMember(ResourceAclMember $resourceAclMember): self
    {
        if (!$this->resourceAclMembers->contains($resourceAclMember)) {
            $this->resourceAclMembers[] = $resourceAclMember;
            $resourceAclMember->setUser($this);
        }

        return $this;
    }

    public function removeResourceAclMember(ResourceAclMember $resourceAclMember): self
    {
        // set the owning side to null (unless already changed)
        if ($this->resourceAclMembers->removeElement($resourceAclMember) && $resourceAclMember->getUser() === $this) {
            $resourceAclMember->setUser(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectTeamMember>
     */
    public function getProjectTeamMembers(): Collection
    {
        return $this->projectTeamMembers;
    }

    public function addProjectTeamMember(ProjectTeamMember $projectTeamMember): self
    {
        if (!$this->projectTeamMembers->contains($projectTeamMember)) {
            $this->projectTeamMembers->add($projectTeamMember);
            $projectTeamMember->setUsers($this);
        }

        return $this;
    }

    public function removeProjectTeamMember(ProjectTeamMember $projectTeamMember): self
    {
        if ($this->projectTeamMembers->removeElement($projectTeamMember)) {
            // set the owning side to null (unless already changed)
            if ($projectTeamMember->getUsers() === $this) {
                $projectTeamMember->setUsers(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserListRanking[]
     */
    public function getUserListRankings(): Collection
    {
        return $this->userListRankings;
    }

    public function addUserListRanking(UserListRanking $userListRanking): self
    {
        if (!$this->userListRankings->contains($userListRanking)) {
            $this->userListRankings[] = $userListRanking;
            $userListRanking->setUser($this);
        }

        return $this;
    }

    public function removeUserListRanking(UserListRanking $userListRanking): self
    {
        if (!$this->userListRankings->removeElement($userListRanking)) {
            return $this;
        }
        if ($userListRanking->getUser() !== $this) {
            return $this;
        }
        $userListRanking->setUser(null);

        return $this;
    }

    /**
     * @return Collection|Document[]
     */
    /*
    public function getDocuments(): Collection
    {
    return $this->documents;
    }

    public function addDocument(Document $document): self
    {
    if (!$this->documents->contains($document)) {
    $this->documents[] = $document;
    $document->setTenant($this);
    }

    return $this;
    }

    public function removeDocument(Document $document): self
    {
    if (!$this->documents->removeElement($document)) {
    return $this;
    }

    if ($document->getTenant() !== $this) {
    return $this;
    }

    $document->setTenant(null);
    return $this;
    }
    */
    /**
     * @return Collection|HelpDeskTopic[]
     */
    public function getHelpDeskTopics(): Collection
    {
        return $this->helpDeskTopics;
    }

    public function addHelpDeskTopic(Topic $helpDeskTopic): self
    {
        if (!$this->helpDeskTopics->contains($helpDeskTopic)) {
            $this->helpDeskTopics[] = $helpDeskTopic;
            $helpDeskTopic->setCreateBy($this);
        }

        return $this;
    }

    public function removeHelpDeskTopic(Topic $helpDeskTopic): self
    {
        if (!$this->helpDeskTopics->removeElement($helpDeskTopic)) {
            return $this;
        }
        if ($helpDeskTopic->getCreateBy() !== $this) {
            return $this;
        }
        $helpDeskTopic->setCreateBy(null);

        return $this;
    }

    /**
     * @return Collection|Error[]
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function addError(Error $error): self
    {
        if (!$this->errors->contains($error)) {
            $this->errors[] = $error;
            $error->setUser($this);
        }

        return $this;
    }

    public function removeError(Error $error): self
    {
        // set the owning side to null (unless already changed)
        if ($this->errors->removeElement($error) && $error->getUser() === $this) {
            $error->setUser(null);
        }

        return $this;
    }

    public function getMobilePhoneNumber(): ?PhoneNumber
    {
        return $this->mobilePhoneNumber;
    }

    public function setMobilePhoneNumber(?PhoneNumber $mobilePhoneNumber): self
    {
        $this->mobilePhoneNumber = $mobilePhoneNumber;

        return $this;
    }

    public function getDirectPhoneNumber(): ?PhoneNumber
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
