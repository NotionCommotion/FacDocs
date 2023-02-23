Non-existing services:
-- static::getContainer()->get('debug.api_platform.serializer.normalizer.item')
-- static::getContainer()->get('Symfony\Component\Serializer\Debug\TraceableNormalize')
-- static::getContainer()->get('ApiPlatform\Serializer\ItemNormalizer')

Getting app.test.api.request.service and ApiRequestService::class returns the same instance.

No difference whether I get api_platform.serializer.normalizer.item directly or via other auto-wired object.
