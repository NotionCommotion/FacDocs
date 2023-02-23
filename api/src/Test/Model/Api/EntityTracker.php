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

namespace App\Test\Model\Api;
use App\Entity\Organization\Tenant;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\Acl\AccessControlAwareInterface;
//use ApiPlatform\Serializer\ItemNormalizer;
//use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Service\ManualSerializerService;
class EntityTracker
{
    private const IGNORED_PROPERTIES=['id', 'createBy', 'createAt', 'updateBy', 'updateAt', 'publicId', 'tenant', 'organization'];
    private array $ignoredProperties;
    private object $entityClone;
    private array $normalizedEntityClone;

    public function __construct(private Tenant|BelongsToTenantInterface|AccessControlAwareInterface $entity, private ManualSerializerService $normalizer, private array $serializerContext, private array $serializerOptions=[])
    {
        // $serializerOptions are: [normalize' => true, 'getContext' => true, 'format' => null, 'ignoredProperties' => [], 'excludeUriTemplate' => true, 'enable_max_depth' => false, 'enable_circular_reference_handler'=>false];
        $this->entityClone = clone $this->entity;
        $this->normalizedEntityClone = $this->normalize();
    }

    public function __call($name, $arguments)
    {
        if(is_callable([$this->entity, $name])) {
            return $this->entity->$name(...$arguments);
        }
        trigger_error(sprintf('Call by %s::%s to undefined method %s::%s', debug_backtrace()[1]['class'], debug_backtrace()[1]['function'], __CLASS__, $name), E_USER_ERROR);
    }

    public function diff(bool $updateClone=true): array
    {
        $diff = $this->array_diff_assoc_recursive($this->normalize(), $this->normalizedEntityClone);
        if($updateClone) {
            $this->sync();
        }
        return $diff;
    }

    public function diffObject(bool $updateClone=true): array
    {
        $diff = $this->_diff($this->entityClone, $this->entity, false);
        if($updateClone) {
            $this->sync();
        }
        return $diff;
    }

    public function sync(): void
    {
        $this->entityClone = clone $this->entity;
        $this->normalizedEntityClone = $this->normalize();
    }

    public function getErrors(object $entity): array
    {
        return $this->_diff($this->entity, $entity, true);
    }

    public function getClass(): string
    {
        return get_class($this->entity);
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function normalize(...$arguments):array
    {
        array_unshift($arguments, $this->entity);
        return $this->_normalize(...$arguments);
    }

    // Options are: ['normalize' => true, 'getContext' => true, 'format' => null, 'ignoredProperties' => [], 'excludeUriTemplate' => true, 'enable_max_depth' => false, 'enable_circular_reference_handler'=>false];
    private function _normalize(object $entity, array $serializerContext = [], array $serializerOptions=[]):array
    {
        return $this->normalizer->normalize($entity, $serializerOptions['format']??null, array_merge($this->serializerContext, $serializerContext), array_merge(['ignoredProperties'=>self::IGNORED_PROPERTIES], $this->serializerOptions, $serializerOptions));
    }

    protected function array_diff_assoc_recursive(array $array1, array $array2):array
    {
        $difference=[];
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }

    private function _diff(object $old, object $new, bool $includeOldValues):array
    {
        if (get_class($old) !== get_class($new)) {
            throw new \InvalidArgumentException("Object classes are different");
        }
        $diff = [];
        $newReflected = new \ReflectionObject($new);
        foreach ((new \ReflectionObject($old))->getProperties() as $oldProperty) {
            $propertyName = $oldProperty->getName();
            if(in_array($propertyName, $this->serializerOptions['ignoredProperties'])) {
                continue;
            }
            if(!$newReflected->hasProperty($propertyName)) {
                continue;
            }
            $newProperty = $newReflected->getProperty($propertyName);
            // Mark private properties as accessible only for reflected class
            $newProperty->setAccessible(true);
            $newValue = $this->toScaler($newProperty->getValue($new));
            if(is_iterable($newValue) || is_iterable($newValue)) {
                $newArray = [];
                $oldArray = [];

                continue;
            }
            elseif(is_scalar($newValue) || is_null($newValue)) {
                $oldProperty->setAccessible(true);
                $oldValue = $this->toScaler($oldProperty->getValue($old));
                if($includeOldValues && !(is_scalar($oldValue) || is_null($oldValue))) {
                    continue;
                }
                if ($oldValue != $newValue) {
                    $diff[$propertyName] = $includeOldValues?['original' => $oldValue,'final' => $newValue]:$newValue;
                }
            }
        }
        return $diff;
    }

    private function handleIterable(\Iterator|array $o)
    {
        $arr = [];
        foreach($o as $k=>$v) {

        }
    }

    private function toScaler(mixed $v)
    {
        if(is_scalar($v)) {
            return $v;
        }
        if($v instanceof \Stringable) {
            return (string) $v;
        }
        if($v instanceof \JsonSerializable) {
            return $v->jsonSerialize();
        }
        if(is_array($v)){
            $a=[];
            foreach($v as $x) {
                $x = $this->toScaler($x);
                if(is_scalar($x)) {
                    $a[] = $x;
                }
            }
            return $a;
        }
        return $v;
    }
}
