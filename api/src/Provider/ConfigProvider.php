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
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserRetreiverService;
use App\Model\Config\Config;

final class ConfigProvider implements ProviderInterface
{
    public function __construct(private UserRetreiverService $userRetreiverService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        return new Config($this->userRetreiverService->getUser());
    }
}
