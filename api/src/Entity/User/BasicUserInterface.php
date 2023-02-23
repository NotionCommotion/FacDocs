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

namespace App\Entity\User;

use Symfony\Component\Uid\Ulid;
use App\Entity\Organization\OrganizationType;

interface BasicUserInterface extends \Stringable
{
    public function getId(): ?Ulid;
    public function getType(): OrganizationType;
    public function getOrganizationId(): Ulid;
    public function getTenantId(): ?Ulid;
    public function isSame(self $user): bool;
    public function isCoworker(self $user): bool;
    
    public function getRoles(): array;
    // Only called if a user's roles are changed
    public function setRoles(array $roles): self;

    public function getClass(): string;

    public function isSystemUser(): bool;
    public function isTenantUser(): bool;
    public function isVendorUser(): bool;
}
