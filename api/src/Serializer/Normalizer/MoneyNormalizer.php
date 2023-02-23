<?php

namespace App\Serializer\Normalizer;

use Exception;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Money\Money;
use Money\Currency;

final class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    /**
     * @param mixed $object Object to normalize
     *
     * @throws InvalidArgumentException when the object given is not a supported type for the normalizer
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
		if (!$object instanceof Money) {
            throw new InvalidArgumentException(sprintf('The object must be an instance of "%s".', Money::class));
        }
        return [
            'amount' => (int)$object->getAmount(),
            'currency' => $object->getCurrency()->getCode(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
      return $data instanceof Money;
    }

    /**
     * @param mixed $data Data to restore
     *
     * @throws InvalidArgumentException Occurs when the arguments are not coherent or not supported
     * @throws UnexpectedValueException Occurs when the item cannot be hydrated with the given data
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Money
    {
        if (!\is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }

        if (!isset($data['amount']) || !isset($data['currency'])) {
            throw new UnexpectedValueException('Missing required keys from data array, must provide "amount" and "currency".');
        }

        try {
            return new Money($data['amount'], new Currency($data['currency']));
        } catch (Exception $e) {
            throw new NotNormalizableValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    //public function supportsDenormalization(mixed $data, string $type, string $format = null /*, array $context = [] */);
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
     return Money::class === $type;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}