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
use App\Entity\Document\Document;
use Doctrine\ORM\EntityManagerInterface;

final class DocumentProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        return $this->entityManager
        ->getRepository(Document::class)
        ->findOneBy(['id' => $uriVariables['id']]);
    }
}
