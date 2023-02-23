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

namespace App\Doctrine\Types;

use App\Entity\Acl\AclPermission;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class AclPermissionType extends Type
{
    private const PERMISSION = 'acl_permission';

    public function getName(): string
    {
        return self::PERMISSION;
    }

    private function createFromValue(int $value): AclPermission
    {
        return AclPermission::createFromValue($value);
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getSmallIntTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if (\is_int($value)) {
            return $this->createFromValue($value);
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['int', 'null']);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof AclPermission) {
            $value->validate();
            return $value->getValue();
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), [AclPermission::class, 'null']);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        // If true, bin/console doctrine:schema:update --dump-sql shows comments??
        return true;
    }
}
