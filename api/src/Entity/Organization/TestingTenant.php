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

namespace App\Entity\Organization;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Organization\TenantRepository;


#[ApiResource(
    operations: [
        new Get(),
        new Put(),
        new Patch(),
        new GetCollection(security: "is_granted('ROLE_SYSTEM_USER')"),
        new Post(security: "is_granted('ROLE_SYSTEM_USER')"),
        new Delete(security: "is_granted('ROLE_SYSTEM_USER')"),
    ],
    denormalizationContext: ['groups' => ['tenant:write', 'organization:write', 'location:write']],
    normalizationContext: ['groups' => ['tenant:read', 'organization:read', 'identifier:read', 'public_id:read', 'user_action:read', 'location:read']]
)]

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class TestingTenant extends Tenant
{
    public function __construct()
    {
        parent::__construct();
		$this->id = Ulid::fromRfc4122('11111111-1111-1111-1111-111111111111');
    }
}
