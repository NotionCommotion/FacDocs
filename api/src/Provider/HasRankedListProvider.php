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

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use App\Service\UserListRankingService;
use ReflectionClass;
use Symfony\Component\HttpFoundation\RequestStack;

// See App\Service\UserListRankingService
/**
 * See services.yaml
 *     # See App\Service\UserListRankingService
    #App\DataProvider\HasRankedListDataProvider:
    #    bind:
    #        $collectionDataProvider: '@api_platform.doctrine.orm.default.collection_data_provider'

 */
final class HasRankedListProvider implements ProviderInterface
{
    public $userListRankingService;
    public $requestStack;
    public $collectionDataProvider;
    /*
    public function __construct(private CollectionDataProviderInterface $collectionDataProvider, private UserListRankingService $userListRankingService, private RequestStack $requestStack)
    {
    }
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        exit(__FILE__.' not done');
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        // Technically, this method does the work and could return false for all requests as getCollection does nothing new.
        return $this->userListRankingService->updateSearchUserRankings($this->requestStack->getCurrentRequest(), new ReflectionClass($resourceClass));
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        return $this->collectionDataProvider->getCollection($resourceClass, $operationName, $context);
    }
}
