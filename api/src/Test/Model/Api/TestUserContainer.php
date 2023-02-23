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

namespace App\Test\Model\Api;

use App\Entity\User\SystemUser;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;

class TestUserContainer
{
    private array $systemUsers=[];
    private array $tenantUsers=[];
    private array $vendorUsers=[];

    public function __construct(EntityTracker ...$entityTrackers)
    {
        $tenants = [];
        $vendors = [];
        $errors = [];
        if(count($entityTrackers)<7) {
            $errors[] = 'At least seven users are required.';
        }
        foreach($entityTrackers as $entityTracker) {
            $user = $entityTracker->getEntity();
            switch($user::class)
            {
                case SystemUser::class:
                    $types['SystemUser'][] = $entityTracker;
                    break;
                case VendorUser::class:
                    $tenants[$user->getTenant()->getId()->toBase32()] = true;
                    $vendors[$user->getOrganization()->getId()->toBase32()][] = $entityTracker;
                    break;
                case TenantUser::class:
                    $tenants[$user->getTenant()->getId()->toBase32()] = true;
                    $types['TenantUser'][] = $entityTracker;
                    break;
                default:
                    $errors[] ='Invalid type: '.$user::class;
            }
        }
        if(count($tenants)!==1){
            $errors[] = 'One and only one tenant is required';
        }
        if(count($types['SystemUser']??[])<1){
            $errors[] = 'At least one system user is required';
        }
        if(count($types['TenantUser']??[])<2){
            $errors[] = 'At least two tenant user are required';
        }
        if(count($vendors)<2){
            $errors[] = 'At least two vendors are required';
        }
        foreach($vendors as $vendor) {
            if(count($vendor)<2){
                $errors[] = 'At least two vendor user are required per vendor';
            }
        }
        if($errors) {
            throw new \Exception(implode(', ', $errors));
        }
        $this->systemUsers = $types['SystemUser'];
        $this->tenantUsers = $types['TenantUser'];
        $this->vendorUsers = array_values($vendors);
    }

    public function getSystemUser(int $user): EntityTracker
    {
        return $this->systemUsers[$user];
    }
    public function getTenantUser(int $user): EntityTracker
    {
        return $this->tenantUsers[$user];
    }
    public function getVendorUser(int $vendor, int $user): EntityTracker
    {
        return $this->vendorUsers[$vendor][$user];
    }

    public function debug():array
    {
        return [
            'systemUsers' => array_map(function($u){return $u->getEntity()->debug();}, $this->systemUsers),
            'tenantUsers' => array_map(function($u){return $u->getEntity()->debug();}, $this->tenantUsers),
            'vendorUsers' => array_map(function($users){return array_map(function($u){return $u->getEntity()->debug();},$users);},$this->vendorUsers),
        ];
    }
}
