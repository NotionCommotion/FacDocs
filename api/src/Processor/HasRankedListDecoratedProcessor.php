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

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\UserListRankingService;
use Symfony\Component\HttpFoundation\RequestStack;

final class HasRankedListDecoratedProcessor implements ProcessorInterface
{
    public $processor;
    public $registry;
    public function __construct(private RequestStack $requestStack, private UserListRankingService $userListRankingService)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []):mixed
    {
        $manager = $this->registry->getRepository(Document::class);
        $repository = $manager->getRepository(Document::class);
        $document = $repository->findOneBy(['id' => $uriVariables['id']]);
        /** @var Asset $data */
        $document->addAsset($data);
        $manager->persist($data);
        $manager->flush();

        return $document;
    }

    public function xxx($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $object = null;
        if ($this->supportsEntity($data, $context)) {
            $this->userListRankingService->updatePersistUserRankings($this->requestStack->getCurrentRequest(), $object);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function supportsEntity($data, array $c = [])
    {
        return $data instanceof UserCreateActionInterface && (
            ($op = $c['collection_operation_name'] ?? null) && \in_array($op, ['post', 'put', 'patch'], true)
            ||
            ($op = $c['graphql_operation_name'] ?? null) && \in_array($op, ['create', 'update'], true)
        );
    }
}
