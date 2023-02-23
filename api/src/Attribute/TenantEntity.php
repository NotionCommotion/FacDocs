<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class TenantEntity
{
    public $userFieldName;
}