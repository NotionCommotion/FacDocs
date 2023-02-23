<?php

namespace App\DataFixtures\Provider;

use Faker\Provider\Base as BaseProvider;

final class TestHelperProvider extends BaseProvider
{
    private const ROLES = [
        'TenantUser' => ['ROLE_TENANT_USER', 'ROLE_TENANT_ADMIN', 'ROLE_TENANT_SUPER'],
        'VendorUser' => ['ROLE_VENDOR_USER', 'ROLE_VENDOR_ADMIN', 'ROLE_VENDOR_SUPER'],
        'SystemUser' => ['ROLE_SYSTEM_USER', 'ROLE_SYSTEM_ADMIN', 'ROLE_SYSTEM_SUPER'],
        'ResourceAclMember' => ['ROLE_SYSTEM_USER', 'ROLE_SYSTEM_ADMIN', 'ROLE_SYSTEM_SUPER'],
    ];

    /*
    public function __construct(...$args)
    {
        // Why is $args empty?
        parent::__construct(...$args);
        // hack to seed the generator
        $this->generator->seed(time());
    }
    */
    public function resourceAcl(): array
    {
        return [
            ['resourceAcl' => 'NotComplete'],
        ];
    }
    public function documentAcl(): array
    {
        return [
            ['documentAcl' => 'NotComplete'],
        ];
    }

    public function resourceAclPermission(): array
    {
        $c = ['all', 'none'];
        return ['read' => $c[rand(0,1)], 'update' => $c[rand(0,1)],];
    }
    public function documentAclPermission(): array
    {
        $c = ['all', 'coworker', 'vendor', 'owner', 'none'];
        return ['read' => $c[rand(0,4)], 'create' => $c[rand(0,4)], 'update' => $c[rand(0,4)], 'delete' => $c[rand(0,4)],];
    }

    public function resourceAclPermissionSet(): array
    {
        return $this->permissionSet('resourceAclPermission');
    }
    public function documentAclPermissionSet(): array
    {
        return $this->permissionSet('documentAclPermission');
    }
    private function permissionSet(string $method): array
    {
        return [
            'tenantUserPermission' => $this->$method(),
            'tenantMemberPermission' => $this->$method(),
            'vendorUserPermission' => $this->$method(),
            'vendorMemberPermission' => $this->$method(),
        ];
    }

    public function money(): array
    {
        return [
            'amount' => rand(0,9)*pow(10, rand(1,6)),
            'currency' => $this->generator->currencyCode(),
        ];
    }

    public function location():array
    {
        return [
            'address' => $this->generator->streetAddress(),
            'city' => $this->generator->city(),
            'state' => $this->generator->stateAbbr(), 
            'zipcode' => $this->generator->postcode(),
        ];
    }

    public function phonenumber2(): string
    {
        return rand(0,1)?$this->generator->e164PhoneNumber():$this->generator->tollFreePhoneNumber();
    }

    public function tenantUserRoles(): array
    {
        return [self::ROLES['TenantUser'][rand(0,2)]];
    }
    public function vendorUserRoles(): array
    {
        return [self::ROLES['VendorUser'][rand(0,2)]];
    }
    public function systemUserRoles(): array
    {
        return [self::ROLES['SystemUser'][rand(0,2)]];
    }
    public function resourceMemberRoles(): array
    {
        return [self::ROLES['ResourceAclMember'][rand(0,2)]];
    }
    public function allUserRoles(): array
    {
        return array_merge(...array_values(self::ROLES))[rand(0,6)];
    }

    public function fixStatus():string
    {
        return 'fixStatus';
    }
}