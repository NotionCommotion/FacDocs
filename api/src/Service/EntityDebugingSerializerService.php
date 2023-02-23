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

namespace App\Service;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final class EntityDebugingSerializerService
{
    //public function __construct(){}

    public function serialize(object $entity, array $ignoredProperties = [], bool $excludeUriTemplate=true):array
    {
        $reflection = new \ReflectionClass(get_class($entity));
        $attributes = $reflection->getAttributes();

        $contexts = [];
        foreach ($attributes as $attribute) {
            if($attribute->getName() === 'ApiPlatform\Metadata\ApiResource') {
                $arguments = $attribute->getArguments();
                if(isset($arguments['uriVariables']) || ($excludeUriTemplate && isset($arguments['uriTemplate']))) {
                    continue;
                }
                foreach ($arguments['operations']??[] as $operation){
                    if(get_class($operation)==='ApiPlatform\Metadata\Get') {
                        $getcontext = $operation->getNormalizationContext();
                        break;
                    }
                }
                $contexts[] = array_merge_recursive($arguments['normalizationContext']??[], $getcontext??[]);
            }
        }
        $context = [];
        for (end($contexts); key($contexts)!==null; prev($contexts)){
            $context = array_merge_recursive($context, current($contexts));
        }

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return sprintf('<<RECURSION: %s (%s)>>', get_class($object), $object->getId());
            },
        ];
        if($ignoredProperties) {
            $defaultContext[AbstractNormalizer::IGNORED_ATTRIBUTES] = $ignoredProperties;
        }
        if($context['enable_max_depth']??false) {
            $defaultContext[AbstractNormalizer::MAX_DEPTH_HANDLER] = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
                return sprintf('<<MAX_DEPTH: %s (%s)>>', get_class($innerObject), $innerObject->getId());
            };
            //$defaultContext[AbstractObjectNormalizer::CIRCULAR_REFERENCE_LIMIT] = 1;
        }
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())), null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer]);
        return $serializer->normalize($entity, null, $context);
    }
}
