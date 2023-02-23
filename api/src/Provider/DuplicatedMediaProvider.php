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

use App\Entity\Document\DuplicatedMedia;
use App\Entity\Document\PhysicalMedia;
use App\Entity\Organization\TenantInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserRetreiverService;

final class DuplicatedMediaProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private UserRetreiverService $userRetreiverService)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        $tenant = $this->getTenant();
        if ($operation instanceof CollectionOperationInterface) {
            foreach ($this->entityManager->getRepository(PhysicalMedia::class)->getDuplicatedMediaFiles($tenant) as $duplicatedMediaFile) {
                yield $this->newDuplicatedMedia($duplicatedMediaFile, $tenant);
            }
        }
        else {
            if ($duplicatedMediaFile = $this->entityManager->getRepository(PhysicalMedia::class)->getDuplicatedMediaFile($tenant, $uriVariables['id'])) {
                return $this->newDuplicatedMedia($duplicatedMediaFile, $tenant);
            }
            return null;
        }
    }

    private function newDuplicatedMedia($physicalMedia, TenantInterface $tenant): DuplicatedMedia
    {
        return new DuplicatedMedia($physicalMedia->getId(), $physicalMedia->getSize(), $physicalMedia->getMediaType(), $physicalMedia->getMediaSubscribers()->filter(fn($media) => $tenant===$media->getTenant()));
    }

    private function getTenant(): TenantInterface
    {
        return $this->userRetreiverService->getUser()->getTenant();
    }
}
