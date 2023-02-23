<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
// What does this do?
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
// Only needed if I need to use the Symfony serializer to serialize the object
//use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
//use Symfony\Component\DependencyInjection\Attribute\Autowire;
class PhoneNumberNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    private $phoneUtil;

    public function __construct(
        private $region = PhoneNumberUtil::UNKNOWN_REGION,
        private $format = PhoneNumberFormat::E164,
        //#[Autowire(service: ObjectNormalizer::class)]
        //private NormalizerInterface $normalizer
    ){
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    public function normalize(mixed $object, string $format = null, array $context = []):string
    {
        return $this->phoneUtil->format($object, $this->format);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): PhoneNumber
    {
        try {
            return $this->phoneUtil->parse($data, $this->region);
        } catch (NumberParseException $e) {
            try {
                return $this->phoneUtil->parse($data, 'US');
            } catch (NumberParseException $e) {
                throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof PhoneNumber;
    }

    //public function supportsDenormalization(mixed $data, string $type, string $format = null /*, array $context = [] */);
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === PhoneNumber::class;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
