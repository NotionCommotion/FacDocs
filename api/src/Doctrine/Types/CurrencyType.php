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

use InvalidArgumentException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Money\Currency;

class CurrencyType extends Type
{
    final const NAME = 'currency';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform):string
    {
        // If desired, change to length 255 and fixed false.
        return $platform->getVarcharTypeDeclarationSQL([
            'length' => 3,
            'fixed' => true,
        ]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof Currency) {
            return $value;
        }
        try {
            $currency = new Currency($value);
        } catch (InvalidArgumentException) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
        return $currency;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof Currency) {
            return $value->getCode();
        }
        try {
            $currency = new Currency($value);
        } catch (InvalidArgumentException) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
        return $currency->getCode();
    }

    public function getName():string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform):bool
    {
        return true;
    }
}