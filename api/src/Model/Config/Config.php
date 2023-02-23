<?php

/*
Future.  Add public models here.  Not complete.  Maybe get rid of.
*/
declare(strict_types=1);

namespace App\Model\Config;

use ApiPlatform\Metadata\ApiResource;
use App\Processor\ConfigProcessor;
use App\Provider\ConfigProvider;

#[ApiResource(provider: ConfigProvider::class, processor: ConfigProcessor::class)]
class Config
{
    public function __construct(private UserInterface $user, private ConfigInterface $type)
    {
    }
}
