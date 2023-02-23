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

namespace App\Entity\Acl;

use App\Exception\InvalidAclPermissionException;
use ApiPlatform\Metadata\ApiProperty;

//#[NotExposed]
//Confirm whether or not this class should not be an ApiResource.
// Needs to be an ApiResource to show example changes, but results in error: Nested documents for attribute \"permission\" are not allowed. Use IRIs instead.
// Not high priority but consider allowing values not provided unchanged and constructor arguments non-nullable and required.
class AclPermission// implements \JsonSerializable
{
    private const READ      = [0b000000000111, 0];
    private const UPDATE    = [0b000000111000, 3];
    private const CREATE    = [0b000111000000, 6]; // Documents only
    private const DELETE    = [0b111000000000, 9]; // Documents only

    public function __construct(
        #[ApiProperty(openapiContext: ['example' => 'all'])]
        private AclPermissionEnum $read,
        #[ApiProperty(openapiContext: ['example' => 'owner'])]
        private AclPermissionEnum $update,
        // Consider allowing create and delete be null for resource acl.
        #[ApiProperty(openapiContext: ['example' => 'none'])]
        private AclPermissionEnum $create,
        #[ApiProperty(openapiContext: ['example' => 'none'])]
        private AclPermissionEnum $delete
    )
    {
        $this->validateCreate();
    }

    public static function createFromValue(int $value=0): self
    {
        return new self(
            AclPermissionEnum::from(($value  & self::READ[0])   >> self::READ[1]),
            AclPermissionEnum::from(($value  & self::UPDATE[0]) >> self::UPDATE[1]),
            AclPermissionEnum::from(($value  & self::CREATE[0]) >> self::CREATE[1]),
            AclPermissionEnum::from(($value  & self::DELETE[0]) >> self::DELETE[1]),
        );
    }

    public static function isValid(int $value): bool
    {
        // Change?
        return self::_isValid($value, self::READ) && self::_isValid($value, self::UPDATE) && self::_isValid($value, self::CREATE) && self::_isValid($value, self::DELETE);
    }

    private static function _isValid(int $value, array $type): bool
    {
        return AclPermissionEnum::isValid(($value  & $type[0])   >> $type[1]);
    }

    public static function create(?string $read=null, ?string $update=null, ?string $create=null, ?string $delete=null): self
    {
        //return new self($read?AclPermissionEnum::fromName($read):null, $update?AclPermissionEnum::fromName($update):null, $create?AclPermissionEnum::fromName($create):null, $delete?AclPermissionEnum::fromName($delete):null);
        return new self(AclPermissionEnum::fromName($read??'NONE'), AclPermissionEnum::fromName($update??'NONE'), AclPermissionEnum::fromName($create??'NONE'), AclPermissionEnum::fromName($delete??'NONE'));
    }

    public static function createFromArray(array $data):self
    {
        return new self(AclPermissionEnum::fromName($data['read']??$data['READ']??'NONE'), AclPermissionEnum::fromName($data['update']??$data['UPDATE']??'NONE'), AclPermissionEnum::fromName($data['create']??$data['CREATE']??'NONE'), AclPermissionEnum::fromName($data['delete']??$data['DELETE']??'NONE'));
    }

    public function validate():void
    {
        $errors = [];
        if($this->read->getPermissionWeight() > $this->update->getPermissionWeight()) {
            $errors[] = 'Update may not be more restrictive than read.';
        }
        // Remove?
        if(!$this->read->allowOwner() && $this->read->getPermissionWeight() > $this->create->getPermissionWeight()) {
            $errors[] = 'Create may not be more restrictive than read.';
        }
        if($this->update->getPermissionWeight() > $this->delete->getPermissionWeight()) {
            $errors[] = 'Delete may not be more restrictive than update.';
        }
        if($errors) {
            throw new InvalidAclPermissionException(implode(', ', $errors).': '.$this->toCrudString());
        }
    }

    public function getValue(): int
    {
        return ($this->read->value << self::READ[1]) | ($this->update->value << self::UPDATE[1]) | ($this->create->value << self::CREATE[1]) | ($this->delete->value << self::DELETE[1]);
    }

    public function setToNoAccess():self
    {
        $this->read = AclPermissionEnum::fromName('NONE');
        $this->update = AclPermissionEnum::fromName('NONE');
        $this->create = AclPermissionEnum::fromName('NONE');
        $this->delete = AclPermissionEnum::fromName('NONE');
        return $this;
    }

    public function toCrudString(bool $readUpdateOnly=false):string
    {
        $arr = [];
        foreach($readUpdateOnly?['read', 'update']:['create', 'read', 'update', 'delete'] as $action) {
            $arr[] = sprintf('%s:%s', substr($action, 0, 1), strtolower(substr($this->$action->name, 0, 1)));
        }
        return implode('.', $arr);
    }


    //public function jsonSerialize():mixed
    public function toArray(bool $readUpdateOnly=false):mixed
    {
        $readUpdate = ['read' => $this->getRead()->name, 'update' => $this->getUpdate()->name];
        return $readUpdateOnly?$readUpdate:array_merge($readUpdate, ['create' => $this->getCreate()->name, 'delete' => $this->getDelete()->name,]);
    }
    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        $keys = ['read','update','create','delete'];
        return $verbose
        ?array_combine($keys, array_map(function(string $tag){return $this->$tag?['name'=>$this->$tag->name, 'value'=>$this->$tag->value]:null;}, $keys))
        :array_combine($keys, array_map(function(string $tag){return $this->$tag->name;}, $keys));
    }

    public function getRead(): AclPermissionEnum
    {
        return $this->read;
    }
    public function setRead(AclPermissionEnum $permission): self
    {
        $this->read = $permission;
        return $this;
    }

    public function getUpdate(): AclPermissionEnum
    {
        return $this->update;
    }
    public function setUpdate(AclPermissionEnum $permission): self
    {
        $this->update = $permission;
        return $this;
    }

    public function getCreate(): AclPermissionEnum
    {
        return $this->create;
    }
    public function setCreate(AclPermissionEnum $permission): self
    {
        $this->create = $permission;
        return $this->validateCreate();
    }

    public function getDelete(): AclPermissionEnum
    {
        return $this->delete;
    }
    public function setDelete(AclPermissionEnum $permission): self
    {
        $this->delete = $permission;
        return $this;
    }

    public function getString(string $name):?string
    {
        return ($p = $this->get($name))?$p->name:null;
    }

    public function get(string $name):?AclPermissionEnum
    {
        $name = strtolower($name);
        return match ($name) {
            'create' => $this->getCreate(),
            'read' => $this->getRead(),
            'update' => $this->getUpdate(),
            'delete' => $this->getDelete(),
            default => null,
        };
    }

    private function validateCreate():self
    {
        if(!$this->create->allowAll() && !$this->create->allowNone()) {
            throw new InvalidAclPermissionException(sprintf('Permission for create may only be "ALL" or "NONE".  "%s" given.', $this->create->name));
        }
        return $this;
    }
}
