<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Based on following but updated legacy code: https://github.com/nepada/phone-number-doctrine/blob/master/src/PhoneNumberDoctrine/PhoneNumberType.php
// Not sure what this is about but has many starts: https://github.com/giggsey/libphonenumber-for-php

declare(strict_types=1);

namespace App\Doctrine\Types;

use libphonenumber\PhoneNumberUtil;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

final class PhoneNumberType extends Type
{
    public function getName(): string
    {
        return 'phone_number';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return (string) $value;
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), [PhoneNumber::class, 'null']);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

       if (\is_string($value)) {
            return PhoneNumberUtil::getInstance()->parse($value, 'US');
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['string', 'null']);
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        // E.164 defines the maximum length as 15 digits, to which we add 1 char for the leading + sign.
        if (!isset($column['length'])) {
            $column['length'] = 16;
        }

        return $platform->getVarcharTypeDeclarationSQL($column);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
