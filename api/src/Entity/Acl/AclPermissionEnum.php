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

enum AclPermissionEnum: int
{
    public const MAP = [
        'NONE'      => 0b000,
        'ALL'       => 0b001,
        'OWNER'     => 0b010,
        'COWORKER'  => 0b011,
        'VENDOR'    => 0b100,
        //'FUTURE'  => 0b101,
    ];
    public const PERMISSION_STRICT_MAP = [
        'ALL'       => 0,
        'VENDOR'    => 25,
        'COWORKER'  => 50,
        'OWNER'     => 75,
        'NONE'      => 100,
    ];
    case NONE       = 0b000; // No access unless allowed by AclMember
    case ALL        = 0b001; // Public
    case OWNER      = 0b010; // Documents only. Only available to owner.
    case COWORKER   = 0b011; // Documents only. Members of the same organization
    case VENDOR     = 0b100; // Documents only. Similar to OWNER but allows Tenant users to access an OWNER record where owned by a vendor user.
    //case FUTURE1  = 0b101;

    public static function fromName(string $name): self
    {
        if(!defined('self::'.strtoupper($name))) {
            throw new InvalidAclPermissionException($name.' is not a valid permission value.');
        }
        return \constant('self::'.strtoupper($name));
    }

    public static function getValueFromName(string $name): int
    {
        return self::MAP[strtoupper($name)];
    }

    public static function isValid(int $value): bool
    {
        // Change?
        return isset(array_flip(self::MAP)[$value]);
    }

    public function getPermissionWeight(): int
    {
        return self::PERMISSION_STRICT_MAP[$this->name];
    }

    public function allowAll(): bool
    {
        return 'ALL' === $this->name;
    }

    public function allowCoworker(): bool
    {
        return 'COWORKER' === $this->name;
    }

    public function allowVendor(): bool
    {
        return 'VENDOR' === $this->name;
    }

    public function allowOwner(): bool
    {
        return 'OWNER' === $this->name;
    }

    public function allowNone(): bool
    {
        return 'NONE' === $this->name;
    }
}
