<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class SingleRandomRowTestFilter extends AbstractFilter
{
    // Got error if this wasn't here????
    public function __construct(private ?\Symfony\Component\HttpFoundation\RequestStack $requestStack=null){}

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
    }
}