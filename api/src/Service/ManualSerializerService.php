<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
//use ApiPlatform\Serializer\ItemNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ReflectionClass;

final class ManualSerializerService
{
    private const DEFAULT_OPTIONS = [
        'normalize' => true,    // Options are true for Get and GetCollection, false for Put, Patch, and Post, and null for all.
        'groups' => [],
        'getContext' => true,
        'format' => null,
        'ignoredProperties' => [],
        'excludeUriTemplate' => true,
        'enable_max_depth' => false,
        'enable_circular_reference_handler'=>false,
    ];

    public function __construct(private NormalizerInterface $serializer, private array $options = [])
    {
        $this->options = $this->getOptions(array_merge(self::DEFAULT_OPTIONS, $options));
    }

    public function getSerializer():NormalizerInterface
    {
        return $this->serializer;
    }

    public function normalize(object $entity, $format = null, array $context = [], array $options=[]):array
    {
        list($getContent, $format) = array_values($this->getMultipleOptions($options, 'getContext', 'format'));
        $context = $getContent?array_merge_recursive($this->getContext($entity, $options), $context):$context;
        $data = $this->serializer->normalize(
            $entity,
            $format??$format??null,
            $context
        );
        return $data;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [], array $options=[]):object
    {
        throw new \Exception(__METHOD__.' not complete');
        //list($getContent, $format) = array_values($this->getMultipleOptions($options, 'getContext', 'format'));
        //$context = $getContent?array_merge_recursive($this->getContext($entity, $options), $context):$context;
        $data = $this->serializer->denormalize(
            $data,
            $type,
            $format,
            $context
        );
        return $data;
    }

    private function getContext(object $entity, array $options = []):array
    {
        $options = $this->getMultipleOptions($options, 'normalize', 'groups', 'ignoredProperties', 'excludeUriTemplate');
        $reflection = new ReflectionClass(get_class($entity));
        $attributes = $reflection->getAttributes();

        $contexts = [];
        foreach ($attributes as $attribute) {
            if($attribute->getName() === ApiResource::class) {
                $arguments = $attribute->getArguments();
                if(isset($arguments['uriVariables']) || ($options['excludeUriTemplate'] && isset($arguments['uriTemplate']))) {
                    continue;
                }
                $operationContext = [];
                foreach ($arguments['operations']??[] as $operation){
                    if($options['normalize']) {
                        if($operation::class===Get::class) {
                            $operationContext = array_merge_recursive($operationContext, $operation->getNormalizationContext()??[]);
                        }
                    }
                    elseif(in_array($operation::class, [Put::class, Patch::class, Post::class])) {
                        $operationContext = array_merge_recursive($operationContext, $operation->getDenormalizationContext()??[]);
                    }
                }
                $contexts[] = array_merge_recursive($options['normalize']?$arguments['normalizationContext']??[]:$arguments['deNormalizationContext']??[], $operationContext);
            }
        }
        $context = [];
        for (end($contexts); key($contexts)!==null; prev($contexts)){
            $context = array_merge_recursive($context, current($contexts));
        }
        $context['groups'] = array_merge($context['groups']??[], $options['groups']??[]);
        return $context;
    }

    private function getDefaultContext(array $options = []):array
    {
        // Not used since handled by ApiPlatform?
        $options = $this->getMultipleOptions($options, 'enable_max_depth', 'enable_circular_reference_handler');
        $defaultContext = [];
        if($options['enable_circular_reference_handler']) {
            $defaultContext[AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER] = function ($object, $format, $context) {
                return sprintf('<<RECURSION: %s (%s)>>', get_class($object), $object->getId());
            };
        }
        if($options['enable_max_depth'] || $context['enable_max_depth']??false) {
            $defaultContext[AbstractNormalizer::MAX_DEPTH_HANDLER] = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                return sprintf('<<MAX_DEPTH: %s (%s)>>', get_class($innerObject), $innerObject->getId());
            };
            //$defaultContext[AbstractObjectNormalizer::CIRCULAR_REFERENCE_LIMIT] = 1;
        }

        return $defaultContext;
    }

    private function getOption(string $name, array $options):mixed
    {
        $options = $this->getOptions($options);
        if(!isset($options[$name])) {
            throw new \Exception(sprintf('Invalid option: %s.', $name));
        }
        return $options[$name];
    }
    private function getMultipleOptions(array $options, string ...$optionNames):array
    {
        $options = $this->getOptions($options);
        if($err = array_diff_key($options, self::DEFAULT_OPTIONS)) {
            throw new \Exception(sprintf('Invalid options: %s.', implode(', ', array_keys($err))));
        }
        return array_intersect_key($options, array_flip($optionNames));
    }
    private function getOptions(array $options):array
    {
        if($err = array_diff_key($options, self::DEFAULT_OPTIONS)) {
            throw new \Exception(sprintf('Invalid options: %s.', implode(', ', array_keys($err))));
        }
        return array_merge($this->options, $options);
    }
}
