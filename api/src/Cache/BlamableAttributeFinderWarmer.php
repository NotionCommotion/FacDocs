<?php

declare(strict_types=1);

namespace App\Cache;

use App\Foo\Bar;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use App\Service\UsesAttributeService;
use Gedmo\Mapping\Annotation\Blameable;

class BlamableAttributeFinderWarmer implements CacheWarmerInterface
{
    public function __construct(private UsesAttributeService $usesAttributeService, private array $attributes)
    {
    }

    public function warmUp($cacheDirectory):array
    {
        foreach($this->attributes as $attribute)
        {
            $this->usesAttributeService->usesAttribute('Foo', $attribute);
        }
        // Also, preload classes and files if desired.
        return [];
    }

    public function isOptional():bool
    {
        return true;
    }
}
