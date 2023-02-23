<?php

declare(strict_types=1);

namespace App\Test\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\TenantInterface;
use App\Entity\Organization\Vendor;
use App\Entity\Organization\VendorInterface;
use App\Entity\Organization\SystemOrganization;
use App\Entity\User\UserInterface;
use App\Entity\User\AbstractUser;
use App\Entity\User\SystemUser;
use App\Entity\ListRanking\RankedListInterface;
use App\Service\EntityDebugingSerializerService;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class TestHelperService
{
    private const TESTING_TENANT_ID	= '11111111-1111-1111-1111-111111111111';
    private const TESTING_VENDOR_NAME	= 'TestingVendor';
    private const PASSWORD = 'testing';
    private const USER_FIRST_NAME = '_TESTER_';

    private const RESOURCE_ACL_PERMISSIONS = [
        'read' => 'all',
        'update' => 'NONE',
    ];
    private const DOCUMENT_ACL_PERMISSIONS = [
        'read' => 'all',
        'create' => 'none',
        'update' => 'owner',
        'delete' => 'owner',
    ];
    // Causes errors:
    public const IGNORED_SERIALIZER_PROPERTIES = ['same', 'hPassword', 'entityPublicId', 'completeAt', 'imposteredOrganization', 'aclResourceHash'];
    // Makes too bid:
    public const ADDITIONAL_IGNORED_SERIALIZER_PROPERTIES = ['createBy', 'updateBy'];

    private NilUlid $testingSystemId;
    private Ulid $testingTenantId;

    private ?TenantInterface $testingTenant=null;
    private ?VendorInterface $testingVendor=null;
    private ?SystemOrganization $testingSystem=null;
    private $testingTenantUsers;
    private $testingVendorUsers;
    private $testingSystemUsers;

    private array $roles;

    public function __construct(private EntityManagerInterface $entityManager, private RoleHierarchyInterface $roleHierarchy, private RandomRecordService $randomRecordService, private EntityDebugingSerializerService $entityDebugingSerializerService)
    {
        $this->testingTenantId = Ulid::fromRfc4122(self::TESTING_TENANT_ID);
        $this->testingSystemId = new NilUlid;
    }

    public function serialize(object $object, array $ignoredProperties = [], bool $excludeUriTemplate=true)
    {
        $ignoredProperties = array_merge(self::IGNORED_SERIALIZER_PROPERTIES, self::ADDITIONAL_IGNORED_SERIALIZER_PROPERTIES, $ignoredProperties);
        return $this->entityDebugingSerializerService->serialize($object, $ignoredProperties, $excludeUriTemplate);
    }

    public function getHeader(?TenantInterface $tenant=null, array $headers=[]): array
    {
        return array_merge($headers, ['uuid' => $this->getId($tenant??$this->getTestingTenant())]);
    }

    public function getPassword(): string
    {
        return self::PASSWORD;
    }
    public function getLogon(UserInterface $user): array
    {
        return $user->getLogon(self::PASSWORD);
    }
    public function getTestingTenant(): TenantInterface
    {
        if(!$this->testingTenant) {
            $this->testingTenant = $this->entityManager->getRepository(Tenant::class)->find($this->testingTenantId);
        }
        return $this->testingTenant;
    }
    public function getTestingVendor(): VendorInterface
    {
        if(!$this->testingVendor) {
            foreach($this->getTestingTenant()->getVendors() as $vendor) {
                if($vendor->getName() === self::TESTING_VENDOR_NAME) {
                    $this->testingVendor = $vendor;

                }
            }
        }
        return $this->testingVendor;
    }

    public function getTestingSystem(): SystemOrganization
    {
        if(!$this->testingSystem) {
            $this->testingSystem = $this->entityManager->getRepository(SystemOrganization::class)->find($this->testingSystemId);
        }
        return $this->testingSystem;
    }

    public function getSystemUser():SystemUser
    {
        return $this->getTestingSystemUser('ROLE_SYSTEM_ADMIN');
    }
    public function getTestingUser(string $type, ?string $role=null): ?UserInterface
    {
        return match ($type) {
            'tenant' => $this->getTestingTenantUser($role),
            'vendor' => $this->getTestingVendorUser($role),
            'system' => $this->getTestingSystemUser($role),
            default => throw new \Exception(sprintf('User type %s is not supported', $type)),
        };
    }
    public function getTestingTenantUser(?string $role=null): ?UserInterface
    {
        if(is_null($this->testingTenantUsers)) {
            $this->testingTenantUsers = $this->getTestingTenantUsers();
        }
        return $this->testingTenantUsers[$role??'ROLE_TENANT_USER']??null;
    }
    public function getTestingVendorUser(?string $role=null): ?UserInterface
    {
        if(is_null($this->testingVendorUsers)) {
            $this->testingVendorUsers = $this->getTestingVendorUsers();
        }
        return $this->testingVendorUsers[$role??'ROLE_VENDOR_USER']??null;
    }
    public function getTestingSystemUser(?string $role=null): ?UserInterface
    {
        if(is_null($this->testingSystemUsers)) {
            $this->testingSystemUsers = $this->getTestingSystemUsers();
        }
        return $this->testingSystemUsers[$role??'ROLE_SYSTEM_USER']??null;
    }
    public function getTestingTenantUsers(): array
    {
        if(is_null($this->testingTenantUsers)) {
            $this->testingTenantUsers = $this->filterUsers($this->getTestingTenant()->getUsers());
        }
        return $this->testingTenantUsers;
    }
    public function getTestingVendorUsers(): array
    {
        if(is_null($this->testingVendorUsers)) {
            $this->testingVendorUsers = $this->filterUsers($this->getTestingVendor()->getUsers());
        }
        return $this->testingVendorUsers;
    }
    public function getTestingSystemUsers(): array
    {
        if(is_null($this->testingSystemUsers)) {
            $this->testingSystemUsers = $this->filterUsers($this->getTestingSystem()->getUsers());
        }
        return $this->testingSystemUsers;
    }

    public function getAllTestingUsers(): array
    {
        return array_merge(array_values($this->getTestingTenantUsers()),array_values($this->getTestingVendorUsers()),array_values($this->getTestingSystemUsers()),);
    }

    private function filterUsers(Collection $users): array
    {
        $filteredUsers = [];
        foreach($users as $user) {
            // First name check only required for system users.
            if($user->getFirstName()===self::USER_FIRST_NAME) {
                $filteredUsers[$user->getLastName()]=$user;
            }
        }
        return $filteredUsers;
    }

    public function getRandomTenant(bool $unique=false): ?TenantInterface
    {
        return $this->randomRecordService->getTenant($unique);
    }
    public function getRandomTenantRecordId(TenantInterface $tenant, string $class, bool $unique=false): mixed
    {
        return $this->randomRecordService->getTenantRecordId($tenant->getId(), $class, $unique);
    }
    public function getRandomTenantRecord(TenantInterface $tenant, string $class, bool $unique=false): ?object
    {
        return $this->randomRecordService->getTenantRecord($tenant->getId(), $class, $unique);
    }
    public function getRandomNonTenantRecordId(string $class, bool $unique=false): mixed
    {
        return $this->randomRecordService->getNonTenantRecordId($class, $unique);
    }
    public function getRandomNonTenantRecord(string $class, bool $unique=false): ?object
    {
        return $this->randomRecordService->getNonTenantRecord($class, $unique);
    }

    public function getUlidRecordById(Ulid|string $id, string $class): HasUlidInterface
    {
        return $this->entityManager->getRepository($class)->find($id);
    }
    public function getTenantById(Ulid $id): TenantInterface
    {
        return $this->entityManager->getRepository(Tenant::class)->find($id);
    }
    public function getUserById(Ulid $id): UserInterface
    {
        return $this->entityManager->getRepository(UserInterface::class)->find($id);
    }
    public function getOrganizationById(Ulid $id): OrganizationInterface
    {
        return $this->entityManager->getRepository(OrganizationInterface::class)->find($id);
    }

    public function getId(object $entity): string|int
    {
        $id=$entity->getId();
        return $id instanceOf Ulid?$id->toRfc4122():$id;
    }

    public function getLocationArray(): array
    {
        return [
            'address' => '101 Main Street',
            'city' => 'Seaside',
            'state' => 'CA',
            'zipcode' => '91234'
        ];
    }

    public function getResourcePermissionArray(): array
    {
        return self::RESOURCE_ACL_PERMISSIONS;
    }
    public function getDocumentPermissionArray(): array
    {
        return self::DOCUMENT_ACL_PERMISSIONS;
    }

    public function getRandomResourcePermissionArray(): array
    {
        $c = ['all', 'none'];
        return [
            'read' => $c[rand(0,1)],
            'delete' => $c[rand(0,2)],
        ];
    }
    public function getRandomDocumentPermissionArray(): array
    {
        $c = ['all', 'owner', 'none'];
        return [
            'read' => $c[rand(0,2)],
            'create' => $c[rand(0,2)],
            'update' => $c[rand(0,2)],
            'delete' => $c[rand(0,2)],
        ];
    }

    public function getDocumentPermissionSetArray(array $tenantUserPermission,array $tenantMemberPermission,array $vendorUserPermission,array $vendorMemberPermission): array
    {
        return [
            'tenantUserPermission' => $tenantUserPermission,
            'tenantMemberPermission' => $tenantMemberPermission,
            'vendorUserPermission' => $vendorUserPermission,
            'vendorMemberPermission' => $vendorMemberPermission,
        ];
    }

    public function setResourceAclPermission(array &$resource, string $type, string $property, $value): self
    {
        $this->validateAclPermission($type, $property, $value, ['read', 'update'], ['all', 'none']);
        $resource['DocumentPermissionSet'][$type][$property] = $value;
        return $this;
    }
    public function setDocumentAclPermission(array &$resource, string $type, string $property, string $value): self
    {
        $this->validateAclPermission($type, $property, $value, ['read', 'create', 'update', 'delete'], ['all', 'coworker', 'vendor', 'owner', 'none']);
        $resource['DocumentPermissionSet'][$type][$property] = $value;
        return $this;
    }

    private function validateAclPermission(string $type, string $property, $value, array $allowedProperties, array $allowedValues): void
    {
        $a = ['tenantUserPermission', 'tenantMemberPermission', 'vendorUserPermission', 'vendorMemberPermission'];
        if(!in_array($type, $a)) {
            throw new \Exception(sprintf('Invalid type "%s".  Must be one of %s.', $type, implode(', ', $a)));
        }
        if(!in_array($property, $allowedProperties)) {
            throw new \Exception(sprintf('Invalid property "%s".  Must be one of %s.', $property, implode(', ', $allowedProperties)));
        }
        if(!in_array($value, $allowedValues)) {
            throw new \Exception(sprintf('Invalid value "%s".  Must be one of %s.', $value, implode(', ', $allowedValues)));
        }
    }

    public function getRandomString(string $s): string
    {
        return $s.'-'.random_int(0, getrandmax());
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function arrayToString(array $a): string
    {
        array_walk($a, function(&$v, $k) {
            $v = "{$k}:{$v}";
        });
        return implode(', ', $a);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getRoleHierarchy(?array $originalRoles=null):array
    {
        $originalRoles = $originalRoles??$this->roleHierarchy->getReachableRoles();

        $roles = [];

        /**
         * Get all unique roles
         */
        foreach ($originalRoles as $originalRole => $inheritedRoles) {
            foreach ($inheritedRoles as $inheritedRole) {
                $roles[$inheritedRole] = [];
            }

            $roles[$originalRole] = [];
        }

        /**
         * Get all inherited roles from the unique roles
         */
        foreach ($roles as $key => $role) {
            $roles[$key] = array_values($this->getInheritedRoles($key, $originalRoles));
        }

        return $roles;  //array_values($roles);
    }

    private function getInheritedRoles(string $role, array $originalRoles, array $roles = []):array
    {
        /**
         * If the role is not in the originalRoles array,
         * the role inherit no other roles.
         */
        if (!array_key_exists($role, $originalRoles)) {
            return $roles;
        }

        /**
         * Add all inherited roles to the roles array
         */
        foreach ($originalRoles[$role] as $inheritedRole) {
            $roles[$inheritedRole] = $inheritedRole;
        }

        /**
         * Check for each inhered role for other inherited roles
         */
        foreach ($originalRoles[$role] as $inheritedRole) {
            return $this->getInheritedRoles($inheritedRole, $originalRoles, $roles);
        }
    }
}
