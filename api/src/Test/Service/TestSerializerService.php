<?php

declare(strict_types=1);

namespace App\Test\Service;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Metadata\Post;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use App\Service\DebugTesterService;

final class TestSerializerService
{    
    public const OPERATION_ATTRIBUTE_KEY = 'serialize';

    public function __construct(private readonly SerializerInterface $serializer, private readonly SerializerContextBuilderInterface $serializerContextBuilder, private ClassMetadataFactoryInterface $classMetadataFactory, private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private DebugTesterService $debugTesterService)
    {
    }

    public function serialize(object $object, string $format='json', string $domain='facdocs.zadaba.com'):array
    {
        $operation = $this->getOperation($object);
        $context = $this->getContext($operation, $domain);
        return $this->serializer->serialize($object, $format, $context);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        $normalizer = new ObjectNormalizer($this->classMetadataFactory);
        $serializer = new Serializer([$normalizer]);
        //$operation = $this->getOperation($object);
        //$context = $this->getContext($operation, $domain);
        return $serializer->denormalize($data, $type, $format, $context);
        return $this->serializer->denormalize($data, $type, $format, $context);
    }

    private function getOperation(object $object):Post
    {
        $default=null;
        foreach($this->resourceMetadataFactory->create($object::class) as $resource) {
            foreach($resource->getOperations() as $operation) {
                if($operation->getMethod()!=='POST') {
                    continue;
                }
                if(!$operation->getExtraProperties()) {
                    return $operation;
                }
                $default = $operation;
            }
        }
        return $default;
    }

    private function getContext(Post $operation, string $domain): array
    {
        $path = explode('.', $operation->getUriTemplate())[0];
        $context = $operation->getNormalizationContext() ?? [];
        $context['operation_name'] = $operation->getName();
        $context['operation'] = $operation;
        $context['resource_class'] = $operation->getClass();
        $context['skip_null_values'] ??= true;
        $context['iri_only'] ??= false;
        $context['request_uri'] = $path;
        $context['uri'] = $domain.$path;
        $context['input'] = $operation->getInput();
        $context['output'] = $operation->getOutput();
        $context['api_allow_update'] = true;
        if ($operation->getTypes()) {
            $context['types'] = $operation->getTypes();
        }
        if ($operation->getUriVariables()) {
            $context['uri_variables'] = [];
            foreach (array_keys($operation->getUriVariables()) as $parameterName) {
                // Fix!
                $context['uri_variables'][$parameterName] = $parameterName;
            }
        }
        return $context;
    }
}
