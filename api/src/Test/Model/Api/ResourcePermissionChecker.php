<?php

declare(strict_types=1);

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;
use App\Entity\Acl\HasAclInterface;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\HasDocumentAclInterface;
use App\Entity\Acl\AclPermissionSet;
use App\Entity\Acl\AclPermission;
use App\Security\Service\AccessRoleService;
use Symfony\Component\Uid\Ulid;

class ResourcePermissionChecker
{
    private const RESOURCE_ACL_CLASSES = [
        'App\Entity\User\TenantUser',
        'App\Entity\Asset\Asset',
        'App\Entity\User\VendorUser',
        'App\Entity\Organization\Vendor',
        'App\Entity\Project\Project',
        'App\Entity\DocumentGroup\DocumentGroup',
        'App\Entity\Specification\CustomSpecification',
        'App\Entity\Archive\Template',
        'App\Entity\Archive\Archive'
    ];

    private const USER_ROLE_TYPES = ['USER', 'ADMIN'];  //, 'SUPER'];
    private const ACTION_ROLE_TYPES = ['READ', 'UPDATE', 'MANAGE']; //, 'CREATE', 'DELETE'

    private const ACTION_METHOD_MAP = [
        'READ'  =>  'GET',
        'UPDATE'=>  'PUT',
        'CREATE'=>  'POST',
        'DELETE'=>  'DELETE'
    ];

    private const DEFAULT_STATUS_CODE_MAP = [
        'GET'       => 200,
        'POST'      => 201,
        'PUT'       => 200,
        'PATCH'     => 200,
        'DELETE'    => 204,
    ];

    private const UNAUTHORIZED_STATUS_CODE = 403;

    public function __construct(private UserInterface $user, private HasAclInterface $resource, private AccessRoleService $accessRoleService, private bool $errorUponDuplicateAcceptance)
    {
    }

    public function getAuthorizationStatus(string $action, ?int $overridenSuccessStatusCode=null): ResourceAuthorizationStatus
    {
        $isAuthorized = $this->isAuthorized($action);
        return new ResourceAuthorizationStatus($action, $this->user, $this->resource, $overridenSuccessStatusCode??$this->_getAnticipatedStatusCode($action, $isAuthorized), $isAuthorized);
    }

    public function debug(): array
    {
        return [
            'user'    =>    $this->user->debug(),
            'resource'    =>    $this->resource->debug(),
            'errorUponDuplicateAcceptance'	=>	$this->errorUponDuplicateAcceptance,
        ];
    }

    public function getAnticipatedStatusCode(string &$action): int
    {
        return $this->_getAnticipatedStatusCode($action, $this->isAuthorized($action));
    }
    private function _getAnticipatedStatusCode(string $action, bool $isAuthorized): int
    {
        return $isAuthorized
        ?self::DEFAULT_STATUS_CODE_MAP[self::ACTION_METHOD_MAP[$action]]
        :self::UNAUTHORIZED_STATUS_CODE;
    }

    public function isAuthorized(string &$action): bool
    {
        // If $errorUponDuplicateAcceptance is true, throw exception if permission is granted based on multiple criteria (i.e. user role and member role or ACL, etc).

        $action = strtoupper($action);
        if(!isset(self::ACTION_METHOD_MAP[$action])) {
            throw new \Exception($action.' not allowed.');
        }
        $allowed = [];

        $requiredRole = $this->getRequiredActionRole($this->resource, $action);

        if($this->accessRoleService->isUserGranted($this->user, $requiredRole)) {
            $allowed[] = 'user role';
        }

        $acl = $this->resource->getResourceAcl();

        if($this->checkPermission($acl->getPermissionSet()->getUserPermission($this->user), $action)) {
            $allowed[] = 'user acl';
        }

        // TBD whether a member should be able to delete if they have the appropriate role.  Currently not.
        if(!in_array($action, ['CREATE', 'DELETE']) && ($member = $acl->getMemberByUser($this->user))) {

            //if(($permission = $member->getPermission()->get($action)) && $permission->allowAll()) {
            if($this->checkPermission($member->getPermission(), $action)) {
                $allowed[] = 'member acl';
            }

            if($this->accessRoleService->isMemberGranted($member, $requiredRole)) {
                $allowed[] = 'member role';
            }
        }

        if($this->errorUponDuplicateAcceptance && count($allowed)>1) {
            throw new \Exception(sprintf('Test protocol does not allow more than one means to allow.  %s allow.', implode(', ', $allowed)));
        }
        return (bool) $allowed;
    }

    private function getRequiredActionRole(HasResourceAclInterface $resource, string $action):string
    {
        return match ($action) {
            'CREATE'    =>  self::getActionRole($resource, 'MANAGE'),
            'READ'      =>  self::getActionRole($resource, 'READ'),
            'UPDATE'    =>  self::getActionRole($resource, 'UPDATE'),
            'DELETE'    =>  self::getActionRole($resource, 'MANAGE'),
            //'MANAGE'  =>  self::getActionRole($resource, 'MANAGE'),
            default     =>  throw new \Exception($action.' is not supported')
        };
    }

    private function checkPermission(AclPermission $permission, string $action):bool
    {
        if(!$actionPermission = $permission->get($action)) {
            return false;
        }
        // TBD whether user with UPDATE should be able to READ.
        return $actionPermission->allowAll();   // || ($x);
    }

    // #########  STATIC SUPPORT METHODS ##############
    public static function getResourceAclClasses(): array
    {
        return self::RESOURCE_ACL_CLASSES;
    }

    public static function getUserRoles(UserInterface|string $userClass): array
    {
        return array_map(function(string $userRoleType) use($userClass){return self::getUserRole($userClass, $userRoleType);}, self::USER_ROLE_TYPES);
    }

    public static function getActionRoles(object|string $resourceClass): array
    {
        return array_map(function(string $action) use($resourceClass){return self::getActionRole($resourceClass, $action);}, self::ACTION_ROLE_TYPES);
    }

    public static function getUserRole(UserInterface|string $userClass, string $type): string
    {
        $type = strtoupper($type);
        if(!in_array($type, self::USER_ROLE_TYPES)) {
            throw new \Exception($type. 'is not supported');
        }
        return sprintf('ROLE_%s_%s', strtoupper(str_replace('User', '', self::getShortName($userClass))), $type);
    }

    private static function getActionRole(object|string $resourceClass, string $action): string
    {
        if(is_object($resourceClass)) {
            $resourceClass = $resourceClass::class;
        }
        if(!in_array($resourceClass, self::RESOURCE_ACL_CLASSES)) {
            throw new \Exception('Class '.$class.' is not supported');
        }
        $action = strtoupper($action);
        if(!in_array($action, self::ACTION_ROLE_TYPES)) {
            throw new \Exception('Action '.$action.' is not supported');
        }
        $resourceClass = self::getShortName($resourceClass);
        $resourceClass = match ($resourceClass) {
            'DocumentGroup' => 'DocGroup',
            'CustomSpecification' => 'CustSpec',
            default => $resourceClass,
        };
        return sprintf('ROLE_%s_%s', $action, strtoupper(self::toSnake($resourceClass)));
    }

    private static function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    private static function toSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }


    public static function noAclPermissionSet(array $permissionSet):bool
    {
        foreach($permissionSet as $permission) {
            if(!$this->noAclPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    public static function noAclPermission(array $permission):bool
    {
        foreach($permission as $action) {
            if($action!=='NONE') {
                return false;
            }
        }
        return true;
    }

    // Not used
    public static function isAllowed(string $task, array $userPermission, array $memberPermission=[]):bool
    {
        return $userPermission[$task]??null === 'ALL' || $memberPermission[$task]??null === 'ALL';
    }

    public static function createResourcePermissionSetArray():array
    {
        return self::createPermissionSetArray(['tenantUser', 'tenantMember', 'vendorUser', 'vendorMember'], ['read', 'update']);
    }
    public static function createResourcePermissionSet():AclPermissionSet
    {
        return AclPermissionSet::createFromAssociateArray(self::createResourcePermissionSetArray());
    }
    public static function createDocumentPermissionSetArray():array
    {
        return self::createPermissionSetArray(['tenantUser', 'tenantMember', 'vendorUser', 'vendorMember'], ['read', 'update', 'create', 'delete']);
    }
    public static function createDocumentPermissionSet():AclPermissionSet
    {
        return AclPermissionSet::createFromAssociateArray(self::createDocumentPermissionSetArray());
    }
    public static function createPermissionSetArray(array $userTypes, array $permissionTypes, string $permissionValue='NONE'):array
    {
        return array_fill_keys($userTypes, array_fill_keys($permissionTypes, $permissionValue));
    }
    public static function createResourcePermissionArray():array
    {
        return self::createPermissionArray(['read', 'update']);
    }
    public static function createDocumentPermissionArray():array
    {
        return self::createPermissionArray(['read', 'update', 'create', 'delete']);
    }
    public static function createPermissionArray(array $permissionTypes, string $permissionValue='NONE'):array
    {
        return array_fill_keys($permissionTypes, $permissionValue);
    }

    public static function createPermissionSetFromArray(array $permissionSet):AclPermissionSet
    {
        return AclPermissionSet::createFromAssociateArray($permissionSet);
    }
    public static function createPermissionFromArray(array $permission):AclPermission
    {
        return AclPermission::createFromArray($permission);
    }

    // #########  NOT USED METHODS ##############
    private function getRoleAction(string $role): string
    {
        // Currently doesn't support MANAGE_ACL roles
        $action = explode('_', $role);
        if($action[0]!=='ROLE') {
            throw new \Exception($role. 'is not valid');
        }
        return $action[1];
        return $role === self::getActionRole($class, $action[1])?$action[1]:null;
    }

    private function canRolePerform(string $role):bool
    {
        return match ($role) {
            'ROLE_TENANT_USER' => false,
            'ROLE_VENDOR_USER' => false,
            'ROLE_TENANT_ADMIN' => true,
            'ROLE_VENDOR_ADMIN' => false,
            default => match(strtoupper($action)) {
                'MANAGE' => in_array($this->getRoleAction($role), ['MANAGE']),
                'UPDATE' => in_array($this->getRoleAction($role), ['MANAGE', 'UPDATE']),
                'READ' => in_array($this->getRoleAction($role), ['MANAGE', 'UPDATE', 'READ']),
            }
        };
    }

}
